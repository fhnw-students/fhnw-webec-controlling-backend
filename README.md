
# fhnw-webec-controlling-backend

> Our API extends the API of JIRA with some additional controlling information.

# Getting Started
- First install the php composer on your system [PHP Composer](https://getcomposer.org/download/)
- Than open your terminal and navigate to your project dir
- Run `composer update`
- Now start your webserver and enjoy our api

# Our API

# Data Structures

## User (object) _JIRA User_

## Project (object) extends the _JIRA Project_
+ uid: (number, required) - _ID of the user of our database_
+ pid: (number, required) - _Key of the project same as in JIRA_
+ name: (string) - _Name of the project_
+ weekload: (number) - _Amount hours a project member works in one week_
+ maxhours: (number) - _This is the limit of one member in the project_
+ rangestart: (date) - _When the projects starts_
+ rangeend: (date) - _When the projects ends_
+ description: (string) - _Describes the projects_
+ jira (object) - _JIRA-Project_

## Worklog (object) _JIRA Worklog_

# API
## Headers
All request need following headers.

| Key           | Value            |
| ------------- | ---------------- |
| Content-Type  | application/json |
| Accept        | application/json |
| Authorization | Basic {token}    |

## Login - _GET /login_
### Request
**Body**

```
{
    "username": "john.example@students.fhnw.ch",
    "password": "1234"
}
```

### Response
**200 - OK** Returend if the user has access to JIRA. Contains the JIRA User.

```
{
  "self": "https://www.cs.technik.fhnw.ch/jira/rest/api/2/user?username=john.example@students.fhnw.ch",
  "key": "john.example@students.fhnw.ch",
  "name": "john.example@students.fhnw.ch",
  "emailAddress": "john.example@students.fhnw.ch",
  "avatarUrls": {
    "16x16": "https://www.cs.technik.fhnw.ch/jira/secure/useravatar?size=xsmall&ownerId=john.example%40students.fhnw.ch&avatarId=11035",
    "24x24": "https://www.cs.technik.fhnw.ch/jira/secure/useravatar?size=small&ownerId=john.example%40students.fhnw.ch&avatarId=11035",
    "32x32": "https://www.cs.technik.fhnw.ch/jira/secure/useravatar?size=medium&ownerId=john.example%40students.fhnw.ch&avatarId=11035",
    "48x48": "https://www.cs.technik.fhnw.ch/jira/secure/useravatar?ownerId=john.example%40students.fhnw.ch&avatarId=11035"
  },
  "displayName": "Example John (s)",
  "active": true,
  "timeZone": "Europe/Amsterdam",
  "locale": "en_US",
  "groups": {
    "size": 219,
    "items": []
  },
  "applicationRoles": {
    "size": 1,
    "items": []
  },
  "expand": "groups,applicationRoles"
}
```

**401 - UNAUTHORIZED** Returend when the user credentials are incorrect


## All JIRA Projects of the User - _GET /all/projects_
### Response
**200 - OK** Returend if the user has access to JIRA. Contains ...

```
{
}
```

**401 - UNAUTHORIZED** Returend when the user credentials are incorrect

## Get Project - _GET /projects_
### Response
**200 - OK** Returend if the user has access to JIRA. Contains ...

```
{
}
```

**401 - UNAUTHORIZED** Returend when the user credentials are incorrect

## Create a new Project - _POST /projects_
### Request
**Body**

### Response
**200 - OK** Returend if the user has access to JIRA. Contains ...

```
{
}
```

**401 - UNAUTHORIZED** Returend when the user credentials are incorrect


## Update a Project - _PUT /projects/{key}_
### Request
**Body**

### Response
**200 - OK** Returend if the user has access to JIRA. Contains ...

```
{
}
```

**401 - UNAUTHORIZED** Returend when the user credentials are incorrect


## Delete a Project - _DELETE /projects/{key}_
### Response
**200 - OK** Returend if the user has access to JIRA. Contains ...

```
{
}
```

**401 - UNAUTHORIZED** Returend when the user credentials are incorrect

## Get Worklogs - _GET /projects/{key}/worklogs_
### Request
**Params**
Those Params are optional. If no param is given it will use the date range of the project

| Key           | Value            |
| ------------- | ---------------- |
| dateFrom  	   | (date) - 2015-12-01 |
| dateTo        | (date) - 2015-12-01 |

## Get Members - _GET /projects/{key}/members
### Response
**200 - OK** Returend if the user has access to JIRA. Contains ...

```
{
}
```

**401 - UNAUTHORIZED** Returend when the user credentials are incorrect

## Get Resource Graph Data - _GET /projects/{key}/resources/graph
### Response
**200 - OK** Returend if the user has access to JIRA. Contains ...

```
{
}
```

**401 - UNAUTHORIZED** Returend when the user credentials are incorrect

## Get Efficiency Graph Data - _GET /projects/{key}/efficiency/graph
### Response
**200 - OK** Returend if the user has access to JIRA. Contains ...

```
{
}
```

**401 - UNAUTHORIZED** Returend when the user credentials are incorrect

## Get Team Graph Data - _GET /projects/{key}/team/graph
### Response
**200 - OK** Returend if the user has access to JIRA. Contains ...

```
{
}
```

**401 - UNAUTHORIZED** Returend when the user credentials are incorrect

# Tooling
We use [Postman](http://www.getpostman.com/) to test our API;

- [Collection](fhnw-jira.postman_collection)
- [Environment](fhnw-w3tec-endpoint.postman_environment)





