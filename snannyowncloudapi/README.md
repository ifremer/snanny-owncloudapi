# Snanny Owncloud Api
Restful service that allow to retrieve informations about sensors and observations. 
Packaged as an owncloud plugin. 
Compliant with sensorML, O&M and observations data in csv or netCdf format.

Endpoint : %Owncloud%/apps/snannyowncloudapi/

## Build and Deploy

Tested with Owncloud 8.1

Copy the snannyowncloudapi folder into your owncloud product in the folder apps


## Install 

Launch Owncloud 

Connect to your owncloud as admin : $host:$port ans select "Applications" in the menu

In the settings select "Activate experimentales apps" and Activate Snanny Owncloud Api


## Public API

This plugin enabled rest services that allow to get modification and content to specific resources

Get the description of a sensor with it's uuid

GET /sml/{uuid} - Return the content file of the sensorML describe by the uuid

GET /sml/{uuid}/download - Automatically download file of the sensorML

GET /sml/{uuid}/info - Retrieve informations of an SML

GET /om/{uuid}/info - Retrieve informations of an O&M


## Private API

Requires administrators privileges :

GET /files?from={from}&to={to} - Return the modified files between two dates

GET /lastfailure - Return the failed indexed files to relaunch indexation

GET /content?id={id} - Return content and description of an internal onwcloud file


