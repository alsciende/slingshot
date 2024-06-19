# Slingshot

Sends JSON documents to any external API.

## Usage

```
/bin/console slingshot <path_to_json_documents> <http_method> <url_of_api>
```

The URL of the external API can contain keys from the JSON documents between square brackets, like '[id]'.
That variable interpolation can also handle multi-dimensional arrays, like '[addresses][0][city]'.

## Example

If the file /opt/data/books/book-1.json holds this content:
```json
{
  "id": 1,
  "title": "Les Misérables",
  "author": "Victor Hugo"
}
```

You can execute this command:
```
/bin/console slingshot /opt/data/books PUT https://api.library.org/books/[id]
```
Which will do a PUT request to https://api.library.org/books/1 with the file content as its JSON body.

Or you can execute this command:
```
/bin/console slingshot /opt/data/books POST https://api.library.org/books
```
Which will do a POST request to https://api.library.org/books with the file content as its JSON body.

