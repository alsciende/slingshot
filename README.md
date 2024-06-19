# Slingshot

Sends JSON documents to any external API.

## Usage

```
/bin/console slingshot <path_to_json_documents> <http_method> <url_of_api>
```

The URL of the external API can contain keys from the JSON documents between square brackets, like '[id]'.
That variable interpolation can also handle multi-dimensional arrays, like '[addresses][0][city]'.

## Example

```
/bin/console slingshot /opt/data/books PUT https://api.library.org/books/[id]
```
