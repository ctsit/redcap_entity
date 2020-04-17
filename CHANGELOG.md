# Change Log
All notable changes to the REDCap Entity project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).


## [2.3.4] - 2020-04-17
### Changed
- Prevent JSON fields from appearing to be empty in EntityForm (Kyle Chesney)


## [2.3.3] - 2020-04-03
### Changed
- add word "Add" to EntityList page button for adding entries to an entity (Kyle Chesney)
- Change text of button that searches EntityList from misleading "Submit" to "Search" (Kyle Chesney)


## [2.3.2] - 2020-03-05
### Changed
- update entityForm pages to address dropdown bug (Kyle Chesney)
- swap user_info for row[key] when parsing emails (Kyle Chesney)
- create AUTHORS.md, move previous devs out of config.json (Kyle Chesney)


## [2.3.1] - 2019-11-18
### Changed
- Fix dropdown rendering error by updating file path for select2.css. (Kyle Chesney)
- Fix typo in module description and README-developer.md. (Philip Chase)

### Added
- Add organizations_demo. (Philip Chase)
- Add Kyle to authors. (Philip Chase)


## [2.3.0] - 2019-06-26
### Changed
- Deliver a warning if a bulk operation does not return true (Kyle Chesney)


## [2.2.0] - 2019-03-31
### Changed
- Replacing __pendencies with __issues (Tiago Bember Simeao)
- Fixing property 'default' value feature. (Tiago Bember Simeao)
- Fixing bulk operation modal rendering. (Tiago Bember Simeao)

### Added
- Finishing up developers documentation (Tiago Bember Simeao)
- Adding advanced protocol example module (Tiago Bember Simeao)
- Adding 'warning' icon handling on StatusMessageQueue (Tiago Bember Simeao)
- Allowing multiple orderBy statements on EntityQuery (Tiago Bember Simeao)
- Adding missing color option for bulk operation button (Tiago Bember Simeao)


## [2.1.2] - 2019-02-01
### Changed
- Removing project exposed filter for project contextualized lists. (Tiago Bember Simeao)


## [2.1.1] - 2019-01-23
### Added
- Adding prefix to exposed filters keys to avoid conflicts with reserved REDCap keys. (Tiago Bember Simeao)


## [2.1.0] - 2019-01-16
### Added
- Add "data" property type to address needs of `redcap_oncore_client` (Tiago Bember Simeao)


## [2.0.1] - 2019-01-12
### Added
- Add DOI to README and Developer's Guide (Philip Chase)


## [2.0.0] - 2019-01-12
### Changed
- Fixing permission problem to set project fields. (Tiago Bember Simeao)
- Refocus README to describe REDCap Entity to REDCap Admins. (Philip Chase)
- Expand developers guide (Tiago Bember Simeao)
- Revise example project used in developers guide (Tiago Bember Simeao)
- Protecting entity properties. (Tiago Bember Simeao)
- Fixing textarea fields, improving alerts display, and limiting entity reference to the project scope. (Tiago Bember Simeao)
- Fixing entity list rows attributes. (Tiago Bember Simeao)
- Improving table deletion security. (Tiago Bember Simeao)
- General improvements on lists and queries. (Tiago Bember Simeao)

### Added
- Adding reset mechanism on building/resetting schema. (Tiago Bember Simeao)
- Adding success message for db table operations. (Tiago Bember Simeao)
- Adding counter to entity list. (Tiago Bember Simeao)
- Adding UI to manage db schemas. (Tiago Bember Simeao)


## [1.0.0] - 2018-10-03
### Summary
 - First release of redcap_entity
