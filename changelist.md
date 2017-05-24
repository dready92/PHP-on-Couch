# Changelist

---
#### [2.0.3]
##### Added
- CouchClient
    + Added query parameters documentation for IDE
- CouchAdmin
    + setRolesToUser($user,$roles)
- Added missing tests for the library(code covered at 92%)
- Added detailed documentation for installation

##### Updated
- Fixed continuous stream (changes, continuous replication)
- Update code examples


---
#### [2.0.2]
##### Added
- Couch
    + getAdapter()
    + setAdapter(CouchHttpAdapterInterface $adapter)
    + initAdapter($opts)
- Adapters
    + CouchHttpAdaterCurl
    + CouchHttpAdapterSocket
- doc/couch.md
- changelist.md

##### Fixed
- Removed echoes that were causing unexpected output
- Fixed some classes import
- Fixed Cookie parsing

---
#### [2.0.1]
##### Added

- CouchClient
    + getIndexes()
    + createIndex(array $fields, $name = null, $ddoc = null, $type = 'json')
    + find($selector, array $fields = null, $sort = null, $index = null)
    + explain($selector, array $fields = null, $sort = null, $index = null)
- CouchClientTest
    + getIndexesTest()
    + createIndexTest()
    + findTest()
    + explainTest()
- changelist.md
- codestyle.md

##### Updated

- Refactored all the code to follow our code style
- Travis config to run CheckStyle 
- Code example to correct syntax

##### Fixed

- Allow to use \_users and \_replicator databases directly

---
#### [2.0]
##### Added

- CouchClient
    + getMemberShip()
    + getConfig($nodeName[,$section,$key])
    + setConfig($nodeName,$section,$key,$value)
    + deleteConfig($nodeName,$section,$key)
- Composer installation now available

#### Updated
- CouchAdmin($client,$options)
- Updated few tests cases

---