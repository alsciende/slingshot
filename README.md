# Slingshot

Sends JSON documents to any external API.

## Usage

```
/bin/console slingshot <path_to_json_documents> <http_method> <url_of_api>
```

The URL of the external API can contain keys from the JSON documents between square brackets, like '[id]'.
That variable interpolation can also handle multi-dimensional arrays, like '[addresses][0][city]'.

## Example

If the file /opt/data/cards/absolution-sphere.json holds this content:
```json
{
  "id": "absolution-sphere",
  "title": "Absolution Sphere",
  "faction": "neogenesis-church"
}
```

You can execute this command:
```
/bin/console slingshot opt/data/cards PUT http://host.docker.internal:8080/cards/[id]
```
Which will do a PUT request to http://host.docker.internal:8080/cards/1 with the file content as its JSON body.

Or you can execute this command:
```
/bin/console slingshot opt/data/cards POST http://host.docker.internal:8080/cards/
```
Which will do a POST request to http://host.docker.internal:8080/cards/ with the file content as its JSON body.

# Options

- dry-run             Stops before making the requests
- clean-after         Deletes used files
