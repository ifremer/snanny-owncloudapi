# Snanny Owncloud Api
Place this app in **owncloud/apps/**

Endpoint : %Owncloud%/apps/snannyowncloudapi/

## Public API

This plugin enabled rest services that allow to get modification and content to specific resources

Get the description of a sensor with it's uuid

GET /sml/{uuid} - Return the content file of the sensorML describe by the uuid

GET /sml/download/{uuid} - Automatically download file of the sensorML

## Private API

Requires administrators privileges :

GET /files?from={from}&to={to} - Return the modified files between two dates

GET /content?id={id} - Return content and description of an internal onwcloud file


