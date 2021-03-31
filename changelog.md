### php-lib changelog

#### v2.1.1

* add getter functions to Json/Envelope


#### v2.1.0

* add .editorconfig
* add error property to JSON/Envelope
* add http code to Api_v2
* add error handling and todo to ApiEndpointGroup_v2
* add sameServerOnly and validRoles properties and checks to ApiEndpoint_v2

#### v2.0

* remove `config` parameter from `processEndpoint` function
* remove roles from authorization in ApiEndpoint base class
* remove unneeded 'use' Firebase from Api_v2
* add Template class


#### v1.3.0

* remove Api/Authorization
* cleanup Authorization in Api_v2 and ApiEndpoint_v2
* convert to use thrown Exceptions for errors

#### v1.2.0

* add Api/Authorization

#### v1.1.0

* add PATCH method

#### v1.0.0

* release as v1.0.0

#### v0.3.0

* add 'error' method to ApiEndpoint_v2

#### v0.2.0

* remove secretKey from ApiEndpoint_v2 to only have in one place

#### v0.1.2

* Api/ - throw Exception when endpoints are missing

#### v0.1.1

* Database/Db.php - throw Exception when function does not exist

#### v0.1.0

* add project files
* add Api classes
* add Json/Envelope
