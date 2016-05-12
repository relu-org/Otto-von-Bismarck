Otto von Bismarck
=================

## Static page uploader to GitHub Pages enabled repository

It takes the JSON formatted data and pushes it via GitHub API (v3) to user selectable repository. It uses GitHub's OAtuh2 for authenticating user.

## Installation

Copy `.env.sample` to `.env` and populate it with your's GitHub Application ID, secret, and callback URL in you need remote signup.

## Usage

Just post as `data` JSON list containing file objects described below. 

```JSON
{
	"filePath":"css\/style.css",
	"fileContent":"PGh0bWw+PGgxPkhlbGxvIFJFTFUhPC9oMT48L2h0bWw+"
}
```

Or you can use provided example `feedme.html`

File object is defined by described below fields:

Field | Type | Description | Example 
--- | --- | --- | --- 
`filePath` | string | Path in the structure of webpage. | `css/style.css`
`fileContent` | base64 string | Contents of the file to upload. | `PGh0bWw+PGgxPkhlbGxvIFJFTFUhPC9oMT48L2h0bWw+`


<!--`update` | optional string | It should contain the sha1 of blob to be updated | 
`commitMsg` | optional string | Custom commit message. Default shown in example column. | Uploaded by relu.org at &lt;date time&gt;-->

## Remote sing up 
Just configure `REMOTE_SIGNUP_CALLBACK` in Your .env file and call `<your-app-url>?remote`

## License

This script is licensed under GNU GPLv3 license.