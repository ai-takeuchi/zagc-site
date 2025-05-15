+++
title = "Welcome ZAGC!" # セクションのタイトル
template = "index.html" # 使用するテンプレート
[extra]
sidebar = false
+++

## ZAGC is your static site's best friend:
<div style="font-size: 1.4em;">
<ul style="list-style: none;">
  <li>🚀 Zola for blazing-fast builds</li>
  <li>🤖 GitHub Actions for smooth automation</li>
  <li>🧠 Cockpit CMS for headless content management</li>
</ul>
Build fast, update easily, and deploy like magic ✨
</div>

## Overview
ZAGC is a lightweight and flexible template stack designed for building and managing static websites. It integrates multiple tools to automate everything from content management to build and deployment:

<ul>
  <li>Zola – A Rust-based static site generator</li>
  <li>Cockpit CMS – A headless CMS</li>
  <li>GitHub Actions – For automated builds and deployments</li>
</ul>

## How It Works
<ol>
  <li>Fetch content and assets from Cockpit</li>
  <li>Convert to Markdown (compatible with Zola)</li>
  <li>Convert and resize uploaded images to .webp</li>
  <li>Build the static site using Zola</li>
  <li>Upload to the public server</li>
  <li>Archive the current website on GitHub (for version history)</li>
</ol>
