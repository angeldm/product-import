# Todo

# Validation

* image not found? ignore, error
* check attribute value uniqueness
* check if reference ids exist
* check for tierprice duplicates and make sure the import still works
* import category-ids: check if the ids exist

## CSV import

* csv import
* xlsx import

* Support import of Magento export csv
* Update table import_history
* check for non-utf-8 in csv imports

## Url rewrites

url_rewrites are created for all store views. No attempt is made to check if they belong to a website that the product is in.

## Extra

* setAllWebsiteIds() adds the product to all websites

## Testing

- test with 500.000 records in the database

## Fields

Are some fields still missing from the import?

https://stackoverflow.com/questions/8585943/magento-1-6-1-what-is-options-container

display_product_options_in
custom_design
custom_design_from
custom_design_to
custom_layout_update
page_layout
product_options_container

## Ever?

* allow access to low level functions to plugin that performs custom database queries
