package main

import (
	"flag"
	"fmt"
	"os"
	"os/exec"
	"path/filepath"
	"strings"
)

var (
	inputDir = "zola/static/uploads"

	sizeFlags = map[string]*bool{
		"thumb":  flag.Bool("thumb", true, "Generate thumbnail size (150px)"),
		"medium": flag.Bool("medium", true, "Generate medium size (300px)"),
		"large":  flag.Bool("large", true, "Generate large size (768px)"),
	}

	outputSizes = map[string]int{
		"thumb":  150,
		"medium": 300,
		"large":  768,
	}

	maxSize      = flag.Int("max-size", 1200, "Maximum size for original image")
	quality      = flag.Int("quality", 85, "WebP quality (0-100)")
	keepOriginal = flag.Bool("keep-original", false, "Keep original image file")
	dryRun       = flag.Bool("dry-run", false, "Print actions without executing")
	verbose      = flag.Bool("verbose", false, "Show detailed logs")
)

func main() {
	flag.Parse()

	err := filepath.Walk(inputDir, processFile)
	if err != nil {
		fmt.Fprintf(os.Stderr, "Walk error: %v\n", err)
		os.Exit(1)
	}
}

func processFile(path string, info os.FileInfo, err error) error {
	if err != nil || info.IsDir() {
		return nil
	}

	if !isImage(path) || strings.HasSuffix(path, ".webp") {
		if *verbose {
			fmt.Printf("[SKIP] %s\n", path)
		}
		return nil
	}

	base := strings.TrimSuffix(path, filepath.Ext(path))
	dir := filepath.Dir(path)
	webpBase := base + ".webp"

	if *verbose || *dryRun {
		fmt.Printf("[CONVERT] %s -> %s (max: %d, quality: %d)\n", path, webpBase, *maxSize, *quality)
	}

	if !*dryRun {
		cmd := exec.Command("convert", path,
			"-resize", fmt.Sprintf("%dx%d>", *maxSize, *maxSize),
			"-quality", fmt.Sprintf("%d", *quality),
			webpBase)
		if out, err := cmd.CombinedOutput(); err != nil {
			fmt.Fprintf(os.Stderr, "convert failed: %s (%v)\n", out, err)
			return nil
		}
	}

	for label, size := range outputSizes {
		if !*sizeFlags[label] {
			continue
		}
		outPath := filepath.Join(dir, fmt.Sprintf("%s-%s.webp", filepath.Base(base), label))
		if *verbose || *dryRun {
			fmt.Printf("[RESIZE] %s -> %s (%dx%d)\n", webpBase, outPath, size, size)
		}

		if !*dryRun {
			cmd := exec.Command("convert", webpBase,
				"-resize", fmt.Sprintf("%dx%d", size, size),
				"-quality", fmt.Sprintf("%d", *quality),
				outPath)
			if out, err := cmd.CombinedOutput(); err != nil {
				fmt.Fprintf(os.Stderr, "resize %s failed: %s (%v)\n", label, out, err)
			}
		}
	}

	if !*keepOriginal {
		if *verbose || *dryRun {
			fmt.Printf("[REMOVE] %s\n", path)
		}
		if !*dryRun {
			if err := os.Remove(path); err != nil {
				fmt.Fprintf(os.Stderr, "Failed to remove %s: %v\n", path, err)
			}
		}
	}

	fmt.Printf("[IMG] %s processed\n", path)
	return nil
}

func isImage(path string) bool {
	ext := strings.ToLower(filepath.Ext(path))
	switch ext {
	case ".jpg", ".jpeg", ".png":
		return true
	default:
		return false
	}
}
