Update the local Trongate documentation to the latest version from GitHub.

Run the following shell command:

```bash
cd _reference/trongate-docs-repo && git pull
```

After pulling, confirm:
1. The pull succeeded and show the output
2. Whether any files were updated
3. If there were updates, briefly summarize which sections changed based on the filenames

If the `_reference/trongate-docs-repo` folder does not exist, run this instead:

```bash
git clone --depth=1 https://github.com/trongate/trongate-docs.git _reference/trongate-docs-repo
```
