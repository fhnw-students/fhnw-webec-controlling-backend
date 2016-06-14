
# fhnw-webec-controlling-backend

> Our API extends the API of JIRA with some additional controlling information.

# Getting Started
- First install the php composer on your system [PHP Composer](https://getcomposer.org/download/)
- Than open your terminal and navigate to your project dir
- Run `composer update`
- Now start your webserver and enjoy our api

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

## Login - _GET /auth/login_
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
**200 - OK** Returend if the user has access to JIRA. Contains all projects of the user

```
{
    "expand": "description,lead,url,projectKeys",
    "self": "https://www.cs.technik.fhnw.ch/jira/rest/api/2/project/10416",
    "id": "10416",
    "key": "BBVESDA",
    "name": "IP-416bb_FlashCard2",
    "avatarUrls": {
      "48x48": "https://www.cs.technik.fhnw.ch/jira/secure/projectavatar?pid=10416&avatarId=11063",
      "24x24": "https://www.cs.technik.fhnw.ch/jira/secure/projectavatar?size=small&pid=10416&avatarId=11063",
      "16x16": "https://www.cs.technik.fhnw.ch/jira/secure/projectavatar?size=xsmall&pid=10416&avatarId=11063",
      "32x32": "https://www.cs.technik.fhnw.ch/jira/secure/projectavatar?size=medium&pid=10416&avatarId=11063"
    },
    "projectTypeKey": "software"
}
```

**401 - UNAUTHORIZED** Returend when the user credentials are incorrect

## Get Project - _GET /projects_
### Response
**200 - OK** Returend if the user has access to JIRA. Contains a specified project

```
{
   "uid": "1",
   "pid": "BBVESDA",
   "name": "Flashcard 2",
   "weekload": "4",
   "maxhours": "180",
   "rangestart": "2015-12-01",
   "rangeend": "2016-10-31",
   "teamSize": "4",
   "description": "description",
   "jira": {
     "expand": "description,lead,url,projectKeys",
     "self": "https://www.cs.technik.fhnw.ch/jira/rest/api/2/project/10416",
     "id": "10416",
     "key": "BBVESDA",
     "name": "IP-416bb_FlashCard2",
     "avatarUrls": {
       "48x48": "https://www.cs.technik.fhnw.ch/jira/secure/projectavatar?pid=10416&avatarId=11063",
       "24x24": "https://www.cs.technik.fhnw.ch/jira/secure/projectavatar?size=small&pid=10416&avatarId=11063",
       "16x16": "https://www.cs.technik.fhnw.ch/jira/secure/projectavatar?size=xsmall&pid=10416&avatarId=11063",
       "32x32": "https://www.cs.technik.fhnw.ch/jira/secure/projectavatar?size=medium&pid=10416&avatarId=11063"
     },
     "projectTypeKey": "software"
   }
}
```

**401 - UNAUTHORIZED** Returend when the user credentials are incorrect

## Create a new Project - _POST /projects_
### Request
**Body**

### Response
**200 - OK** Returend if the user has access to JIRA. Contains the created project

```
{
  "uid": "1",
  "pid": "BBVESDA",
  "name": "name",
  "weekload": "4",
  "maxhours": "180",
  "rangestart": "2015-12-01",
  "rangeend": "2016-10-31",
  "teamSize": "4",
  "description": "description",
  "jira": {
    "expand": "description,lead,url,projectKeys",
    "self": "https://www.cs.technik.fhnw.ch/jira/rest/api/2/project/10416",
    "id": "10416",
    "key": "BBVESDA",
    "description": "",
    "lead": {
      "self": "https://www.cs.technik.fhnw.ch/jira/rest/api/2/user?username=john.example@students.fhnw.ch",
      "key": "john.example@students.fhnw.ch",
      "name": "john.example@students.fhnw.ch",
      "avatarUrls": {
        "16x16": "https://www.cs.technik.fhnw.ch/jira/secure/useravatar?size=xsmall&ownerId=john.example%40students.fhnw.ch&avatarId=11035",
        "24x24": "https://www.cs.technik.fhnw.ch/jira/secure/useravatar?size=small&ownerId=john.example%40students.fhnw.ch&avatarId=11035",
        "32x32": "https://www.cs.technik.fhnw.ch/jira/secure/useravatar?size=medium&ownerId=john.example%40students.fhnw.ch&avatarId=11035",
        "48x48": "https://www.cs.technik.fhnw.ch/jira/secure/useravatar?ownerId=john.example%40students.fhnw.ch&avatarId=11035"
      },
      "displayName": "Example John (s)",
      "active": true
    },
    "components": [
      {
        "self": "https://www.cs.technik.fhnw.ch/jira/rest/api/2/component/10412",
        "id": "10412",
        "name": "Project Mgmt",
        "isAssigneeTypeValid": false
      }
    ],
    "issueTypes": [
      {
        "self": "https://www.cs.technik.fhnw.ch/jira/rest/api/2/issuetype/3",
        "id": "3",
        "description": "A task that needs to be done.",
        "iconUrl": "https://www.cs.technik.fhnw.ch/jira/secure/viewavatar?size=xsmall&avatarId=10318&avatarType=issuetype",
        "name": "Task",
        "subtask": false,
        "avatarId": 10318
      }
    ],
    "assigneeType": "UNASSIGNED",
    "versions": [
      {
        "self": "https://www.cs.technik.fhnw.ch/jira/rest/api/2/version/10654",
        "id": "10654",
        "description": "Development - End of the project",
        "name": "Iteration_4",
        "archived": false,
        "released": false,
        "startDate": "2016-09-05",
        "releaseDate": "2016-10-14",
        "overdue": false,
        "userStartDate": "05/Sep/16",
        "userReleaseDate": "14/Oct/16",
        "projectId": 10416
      }
    ],
    "name": "IP-416bb_FlashCard2",
    "roles": {
      "Developers": "https://www.cs.technik.fhnw.ch/jira/rest/api/2/project/10416/role/10001",
      "Administrators": "https://www.cs.technik.fhnw.ch/jira/rest/api/2/project/10416/role/10002",
      "Users": "https://www.cs.technik.fhnw.ch/jira/rest/api/2/project/10416/role/10000",
      "Tempo Project Managers": "https://www.cs.technik.fhnw.ch/jira/rest/api/2/project/10416/role/10100"
    },
    "avatarUrls": {
      "48x48": "https://www.cs.technik.fhnw.ch/jira/secure/projectavatar?pid=10416&avatarId=11063",
      "24x24": "https://www.cs.technik.fhnw.ch/jira/secure/projectavatar?size=small&pid=10416&avatarId=11063",
      "16x16": "https://www.cs.technik.fhnw.ch/jira/secure/projectavatar?size=xsmall&pid=10416&avatarId=11063",
      "32x32": "https://www.cs.technik.fhnw.ch/jira/secure/projectavatar?size=medium&pid=10416&avatarId=11063"
    },
    "projectTypeKey": "software"
  }
}
```

**401 - UNAUTHORIZED** Returend when the user credentials are incorrect


## Update a Project - _PUT /projects/{key}_
### Request
**Body**

### Response
**200 - OK** Returend if the user has access to JIRA. Contains the updated project

```
{
  "name": "test",
  "weekload": "4",
  "maxhours": "180",
  "rangestart": "2015-12-01",
  "rangeend": "2016-10-31",
  "description": "description",
  "jira": {
    "expand": "description,lead,url,projectKeys",
    "self": "https://www.cs.technik.fhnw.ch/jira/rest/api/2/project/10416",
    "id": "10416",
    "key": "BBVESDA",
    "description": "",
    "lead": {
      "self": "https://www.cs.technik.fhnw.ch/jira/rest/api/2/user?username=john.example@students.fhnw.ch",
      "key": "john.example@students.fhnw.ch",
      "name": "john.example@students.fhnw.ch",
      "avatarUrls": {
        "16x16": "https://www.cs.technik.fhnw.ch/jira/secure/useravatar?size=xsmall&ownerId=john.example%40students.fhnw.ch&avatarId=11035",
        "24x24": "https://www.cs.technik.fhnw.ch/jira/secure/useravatar?size=small&ownerId=john.example%40students.fhnw.ch&avatarId=11035",
        "32x32": "https://www.cs.technik.fhnw.ch/jira/secure/useravatar?size=medium&ownerId=john.example%40students.fhnw.ch&avatarId=11035",
        "48x48": "https://www.cs.technik.fhnw.ch/jira/secure/useravatar?ownerId=john.example%40students.fhnw.ch&avatarId=11035"
      },
      "displayName": "Example John (s)",
      "active": true
    },
    "components": [
      {
        "self": "https://www.cs.technik.fhnw.ch/jira/rest/api/2/component/10412",
        "id": "10412",
        "name": "Project Mgmt",
        "isAssigneeTypeValid": false
      }
    ],
    "issueTypes": [
      {
        "self": "https://www.cs.technik.fhnw.ch/jira/rest/api/2/issuetype/3",
        "id": "3",
        "description": "A task that needs to be done.",
        "iconUrl": "https://www.cs.technik.fhnw.ch/jira/secure/viewavatar?size=xsmall&avatarId=10318&avatarType=issuetype",
        "name": "Task",
        "subtask": false,
        "avatarId": 10318
      }
    ],
    "assigneeType": "UNASSIGNED",
    "versions": [
      {
        "self": "https://www.cs.technik.fhnw.ch/jira/rest/api/2/version/10654",
        "id": "10654",
        "description": "Development - End of the project",
        "name": "Iteration_4",
        "archived": false,
        "released": false,
        "startDate": "2016-09-05",
        "releaseDate": "2016-10-14",
        "overdue": false,
        "userStartDate": "05/Sep/16",
        "userReleaseDate": "14/Oct/16",
        "projectId": 10416
      }
    ],
    "name": "IP-416bb_FlashCard2",
    "roles": {
      "Developers": "https://www.cs.technik.fhnw.ch/jira/rest/api/2/project/10416/role/10001",
      "Administrators": "https://www.cs.technik.fhnw.ch/jira/rest/api/2/project/10416/role/10002",
      "Users": "https://www.cs.technik.fhnw.ch/jira/rest/api/2/project/10416/role/10000",
      "Tempo Project Managers": "https://www.cs.technik.fhnw.ch/jira/rest/api/2/project/10416/role/10100"
    },
    "avatarUrls": {
          "48x48": "https://www.cs.technik.fhnw.ch/jira/secure/projectavatar?pid=10416&avatarId=11063",
          "24x24": "https://www.cs.technik.fhnw.ch/jira/secure/projectavatar?size=small&pid=10416&avatarId=11063",
          "16x16": "https://www.cs.technik.fhnw.ch/jira/secure/projectavatar?size=xsmall&pid=10416&avatarId=11063",
          "32x32": "https://www.cs.technik.fhnw.ch/jira/secure/projectavatar?size=medium&pid=10416&avatarId=11063"
        },
        "projectTypeKey": "software"
      }
}
```

**401 - UNAUTHORIZED** Returend when the user credentials are incorrect


## Delete a Project - _DELETE /projects/{key}_
### Response
**204 - No Content** Returend if the user has access to JIRA.

**401 - UNAUTHORIZED** Returend when the user credentials are incorrect

## Get Worklogs - _GET /projects/{key}/worklogs_
### Request
**Params**
Those Params are optional. If no param is given it will use the date range of the project

| Key           | Value            |
| ------------- | ---------------- |
| dateFrom  	   | (date) - 2015-12-01 |
| dateTo        | (date) - 2015-12-01 |


### Response
**200 - OK** Returend if the user has access to JIRA. Contains all worklogs of the specified project
```
{
    "timeSpentSeconds": 7200,
    "dateStarted": "2015-12-07T00:00:00.000",
    "comment": "Preparation for the customer meeting",
    "self": "https://www.cs.technik.fhnw.ch/jira/rest/tempo-timesheets/3/worklogs/16140",
    "id": 16140,
    "author": {
      "self": "https://www.cs.technik.fhnw.ch/jira/rest/api/2/user?username=john.example@students.fhnw.ch",
      "name": "john.example@students.fhnw.ch",
      "displayName": "Example John (s)",
      "avatar": "https://www.cs.technik.fhnw.ch/jira/secure/useravatar?size=small&ownerId=john.example%40students.fhnw.ch&avatarId=11035"
    }
}
```


## Get Members - _GET /projects/{key}/members
### Response
**200 - OK** Returend if the user has access to JIRA. Contains all members of the project

```
{
  {
    "self": "https://www.cs.technik.fhnw.ch/jira/rest/api/2/user?username=john.example@students.fhnw.ch",
    "name": "john.example@students.fhnw.ch",
    "displayName": "Example John (s)",
    "avatar": "https://www.cs.technik.fhnw.ch/jira/secure/useravatar?size=small&ownerId=john.example%40students.fhnw.ch&avatarId=11035"
  }
}
```

**401 - UNAUTHORIZED** Returend when the user credentials are incorrect

## Get Resource Graph Data - _GET /projects/{key}/resources/graph
### Response
**200 - OK** Returend if the user has access to JIRA. Contains resources data for a graph

```
{
"labels": [
    "50/2015",
    "51/2015"
  ],
  "datasets": [
    {
      "label": "Example John (s)",
      "data": [
        4,
        5
      ]
    },
    {
      "label": "Weekload",
      "data": [
        4,
        8
      ]
    },
    {
      "label": "Target",
      "data": [
        180,
        180
      ]
    }
  ]
}
```

**401 - UNAUTHORIZED** Returend when the user credentials are incorrect

## Get Efficiency Graph Data - _GET /projects/{key}/efficiency/graph
### Response
**200 - OK** Returend if the user has access to JIRA. Contains efficiency data for a graph

```
{
"labels": [
    "50/2015",
    "51/2015"
  ],
  "datasets": [
    {
      "label": "Example John (s)",
      "data": [
        4,
        1
      ]
    },
    {
      "label": "Weekload",
      "data": [
        4,
        4
      ]
    }
  ]
}
```

**401 - UNAUTHORIZED** Returend when the user credentials are incorrect

## Get Team Graph Data - _GET /projects/{key}/team/graph
### Response
**200 - OK** Returend if the user has access to JIRA. Contains team data for a graph

```
{
"labels": [
    "50/2015",
    "51/2015"
  ],
  "datasets": [
    {
      "label": "Planed",
      "data": [
        16,
        32
      ]
    },
    {
      "label": "Real",
      "data": [
        12,
        18
      ]
    }
  ]
}
```

**401 - UNAUTHORIZED** Returend when the user credentials are incorrect

# Tooling
We use [Postman](http://www.getpostman.com/) to test our API;

- [Collection](fhnw-jira.postman_collection)
- [Environment](fhnw-w3tec-endpoint.postman_environment)





