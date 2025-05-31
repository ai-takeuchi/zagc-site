package main

import (
	"encoding/json"
	"flag"
	"fmt"
	"os"
	"path/filepath"
	"regexp"
	"strings"
)

type Item struct {
	ID       string `json:"_id"`
	Title    string `json:"title"`
	Date     string `json:"date"`
	Content  string `json:"content"`
	Modified int    `json:"_modified"`
}

func main() {
	// コマンドライン引数
	inputPath := flag.String("input", "", "Input JSON file path")
	outputDir := flag.String("output", "", "Output directory for markdown files")
	uploadURL := flag.String("upload-url", "", "Original upload URL prefix")
	deployUploadURL := flag.String("deploy-upload-url", "", "Deployed URL prefix (used in markdown)")
	removeMissingDirs := flag.Bool("remove-missing-dirs", false, "Remove directories in output that are missing in JSON")

	flag.Parse()

	if *inputPath == "" || *outputDir == "" || *uploadURL == "" || *deployUploadURL == "" {
		fmt.Fprintln(os.Stderr, "Error: All flags -input, -output, -upload-url, -deploy-upload-url are required.")
		flag.Usage()
		os.Exit(1)
	}

	currentItems, err := loadItems(*inputPath)
	if err != nil {
		fmt.Fprintf(os.Stderr, "Failed to load current content JSON: %v\n", err)
		os.Exit(1)
	}

	// 以前の状態を読み込む
	prevPath := filepath.Join(filepath.Dir(*inputPath), "prev_"+filepath.Base(*inputPath))
	var previousItems map[string]Item
	if _, err := os.Stat(prevPath); err == nil {
		previousItems, _ = loadItems(prevPath)
	} else {
		previousItems = map[string]Item{}
	}

	// 出力ディレクトリ作成
	os.MkdirAll(*outputDir, 0755)

	// 以前存在したが、今回存在しない記事のファイルを削除
	for id := range previousItems {
		if _, ok := currentItems[id]; !ok {
			localPath := filepath.Join(*outputDir, id+".md")
			fmt.Printf("[DEL] %s\n", localPath)
			os.Remove(localPath)
		}
	}

	// Markdown ファイルを生成・更新
	for _, item := range currentItems {
		prevItem, _ := previousItems[item.ID]
		if prevItem.Modified == item.Modified {
			continue
		}

		filename := filepath.Join(*outputDir, item.ID+".md")
		content := fmt.Sprintf(`+++
title = "%s"
date = %s
+++

%s
`, item.Title, item.Date, replaceUploadURL(item.Content, *uploadURL, *deployUploadURL))

		if err := os.WriteFile(filename, []byte(content), 0644); err != nil {
			fmt.Fprintf(os.Stderr, "Failed to write markdown file %s: %v\n", filename, err)
			os.Exit(1)
		}
		fmt.Printf("[WRITE] %s\n", filename)
	}

	// 追加: json に無い記事のディレクトリ削除
	if *removeMissingDirs {
		err := filepath.WalkDir(*outputDir, func(path string, d os.DirEntry, err error) error {
			if err != nil {
				// エラーが発生している場合、WalkDir をスキップ
				return filepath.SkipDir
			}
			// 出力ディレクトリそのものはスキップ
			if !d.IsDir() || path == *outputDir {
				return nil
			}
			dirID := filepath.Base(path)
			if _, ok := currentItems[dirID]; !ok {
				fmt.Printf("[DEL-DIR] %s\n", path)
				// 削除後は SkipDir を返す
				if err := os.RemoveAll(path); err != nil {
					fmt.Fprintf(os.Stderr, "Failed to remove %s: %v\n", path, err)
				}
				return filepath.SkipDir
			}
			return nil
		})
		if err != nil {
			fmt.Fprintf(os.Stderr, "Failed to remove missing directories: %v\n", err)
			os.Exit(1)
		}
	}

	// 現在状態を保存
	if err := os.WriteFile(prevPath, mustMarshal(currentItems), 0644); err != nil {
		fmt.Fprintf(os.Stderr, "Failed to save previous state to %s: %v\n", prevPath, err)
		os.Exit(1)
	}
}

func loadItems(path string) (map[string]Item, error) {
	data, err := os.ReadFile(path)
	if err != nil {
		return nil, err
	}
	var list []Item
	if err := json.Unmarshal(data, &list); err != nil {
		return nil, err
	}
	items := make(map[string]Item)
	for _, a := range list {
		items[a.ID] = a
	}
	return items, nil
}

func mustMarshal(m map[string]Item) []byte {
	var items []Item
	for _, a := range m {
		items = append(items, a)
	}
	data, _ := json.MarshalIndent(items, "", "  ")
	return data
}

func replaceImageExtToWebPIfMatch(url, uploadURL, deployUploadURL string) string {
	if !strings.HasPrefix(url, uploadURL) {
		return url
	}
	url = strings.Replace(url, uploadURL, deployUploadURL, 1)
	extRe := regexp.MustCompile(`(?i)\.(jpg|jpeg|png)$`)
	return extRe.ReplaceAllString(url, ".webp")
}

func replaceUploadURL(content, uploadURL, deployUploadURL string) string {
	uploadURL = strings.TrimRight(uploadURL, "/") + "/"
	deployUploadURL = strings.TrimRight(deployUploadURL, "/") + "/"

	mdImageRe := regexp.MustCompile(`!\[[^\]]*?\]\(([^)]+)\)`)
	content = mdImageRe.ReplaceAllStringFunc(content, func(match string) string {
		m := mdImageRe.FindStringSubmatch(match)
		url := m[1]
		newURL := replaceImageExtToWebPIfMatch(url, uploadURL, deployUploadURL)
		return strings.Replace(match, url, newURL, 1)
	})

	htmlRe := regexp.MustCompile(`(?i)(<(img|source)[^>]+?(src|srcset)=["'])([^"']+)(["'])`)
	content = htmlRe.ReplaceAllStringFunc(content, func(match string) string {
		m := htmlRe.FindStringSubmatch(match)
		prefix := m[1]
		url := m[4]
		suffix := m[5]
		newURL := replaceImageExtToWebPIfMatch(url, uploadURL, deployUploadURL)
		return prefix + newURL + suffix
	})

	return content
}
