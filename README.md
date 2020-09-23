# build-repo

Build script to package [YesWiki] **extensions** and **themes**.

## Usage

```bash
composer install
sh entrypoint.sh path/to/yeswiki-extension path/to/output
```


## Docker setup

```bash
docker build -t yeswiki/yeswiki-build-repo .
```

```bash
docker run --rm -v $(pwd)/yeswiki-extension-test:/github/workspace yeswiki/yeswiki-build-repo /github/workspace /tmp/output
```
