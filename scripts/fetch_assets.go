package main

import (
	"encoding/json"
	"flag"
	"fmt"
	"io"
	"net/http"
	"os"
	"path/filepath"
)

type Asset struct {
	ID   string `json:"_id"`
	Path string `json:"path"`
}

func loadAssets(path string) (map[string]Asset, error) {
	data, err := os.ReadFile(path)
	if err != nil {
		return nil, err
	}

	var list []Asset
	if err := json.Unmarshal(data, &list); err != nil {
		return nil, err
	}

	assets := make(map[string]Asset)
	for _, a := range list {
		assets[a.ID] = a
	}
	return assets, nil
}

func main() {
	baseURL := flag.String("baseurl", "", "Base URL (e.g., http://localhost/cockpit/storage/uploads)")
	jsonPath := flag.String("json", "data/assets.json", "Current assets.json")
	prevPath := flag.String("prev", "data/prev_assets.json", "Previous assets.json")
	outDir := flag.String("out", "zola/static/uploads", "Output directory")
	dryRun := flag.Bool("dry-run", false, "Show what would be done without doing it")
	verbose := flag.Bool("verbose", false, "Show verbose logs")
	flag.Parse()

	if *baseURL == "" {
		fmt.Fprintln(os.Stderr, "Error: --baseurl is required")
		os.Exit(1)
	}

	currentAssets, err := loadAssets(*jsonPath)
	if err != nil {
		fmt.Fprintf(os.Stderr, "Failed to read current assets: %v\n", err)
		os.Exit(1)
	}

	var previousAssets map[string]Asset
	if _, err := os.Stat(*prevPath); err == nil {
		previousAssets, _ = loadAssets(*prevPath)
	} else {
		previousAssets = map[string]Asset{}
	}

	// 削除されたファイル
	for id, asset := range previousAssets {
		if _, ok := currentAssets[id]; !ok {
			localPath := filepath.Join(*outDir, asset.Path)
			fmt.Printf("[DEL] %s\n", localPath)
			if !*dryRun {
				if err := os.Remove(localPath); err != nil && *verbose {
					fmt.Fprintf(os.Stderr, "Failed to delete: %s (%v)\n", localPath, err)
				}
			}
		}
	}

	// 新規または変更されたファイル
	for id, asset := range currentAssets {
		prevAsset, ok := previousAssets[id]
		if ok && prevAsset.Path == asset.Path {
			if *verbose {
				fmt.Printf("[SKIP] %s (unchanged)\n", asset.Path)
			}
			continue
		}

		url := fmt.Sprintf("%s/%s", *baseURL, asset.Path)
		localPath := filepath.Join(*outDir, asset.Path)

		fmt.Printf("[GET] %s\n", asset.Path)
		if *dryRun {
			continue
		}

		if err := os.MkdirAll(filepath.Dir(localPath), 0755); err != nil {
			fmt.Fprintf(os.Stderr, "Failed to create dir for %s: %v\n", asset.Path, err)
			continue
		}

		resp, err := http.Get(url)
		if err != nil || resp.StatusCode != http.StatusOK {
			if resp != nil {
				resp.Body.Close()
			}
			fmt.Fprintf(os.Stderr, "Failed to download %s: %v\n", asset.Path, err)
			continue
		}

		func() {
			defer resp.Body.Close()

			out, err := os.Create(localPath)
			if err != nil {
				fmt.Fprintf(os.Stderr, "Failed to create file %s: %v\n", asset.Path, err)
				return
			}
			defer out.Close()

			if _, err := io.Copy(out, resp.Body); err != nil {
				fmt.Fprintf(os.Stderr, "Failed to write file %s: %v\n", asset.Path, err)
			}
		}()
	}

	// 保存
	if !*dryRun {
		if err := os.WriteFile(*prevPath, mustMarshal(currentAssets), 0644); err != nil {
			fmt.Fprintf(os.Stderr, "Failed to save previous state: %v\n", err)
			os.Exit(1)
		}
	}
}

func mustMarshal(m map[string]Asset) []byte {
	var assets []Asset
	for _, a := range m {
		assets = append(assets, a)
	}
	data, _ := json.MarshalIndent(assets, "", "  ")
	return data
}
