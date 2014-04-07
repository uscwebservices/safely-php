This is a simple example of creating a Hello World application
using safely.php and restful.php parts of the safely-php collection.

## Overview

Hello world is a restful service that RETURNS a "Hello world" message
as a JSON object based on the URL request.

If the service is called without arguments then it returns a simple blog

```javascript
    {"message": "Hello World"}
```

if an additional path argument is provided then it will use the trailing
path element as a name and modify the message accordingly. Path elements
can only be alphabetical characters. E.g. [api/first-name/Sam](./api/first-name/Sam) should
return a message blob like--

```JavaScript
    {"message": "Hello Sam"}
```

This demo will illustrate validating the PATH_INFO variable for a valid name
as well as define and process the route __/api/first-name/\*__. The first letter
of a name is expected to be capitalized and that is reflected in the validation
process so asking for [api/first-name/sam](api/first-name/sam) should return
an error element in our JSON blob.

```JavaScript
    {"message": "I didn't understand your first name.", "status": "error"}
```

The paths are relative to this repository (e.g. safely.php is found at ../../safely.php)
and would have to be adjusted based on your actualy deployment.


