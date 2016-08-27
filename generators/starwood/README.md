# Starwood Properties fetcher

### How to execute

1. Run `php starwood.php` from your console (if you're in a Mac, PHP comes bundled in)
2. Wait (probably good to leave it running all night)

### Notes

* It has a cache system to prevent re-downloading what it already has, based off `propertyId` but also about the list of all properties (saving it in a temporary HTML)
* All unmatched (new / unsaved) Properties will be added to the output `starwood.json` file.

### TO-DOs

1. Remove invalid properties from the JSON (if they ever change it.)
2. Fix any missing properties on the JSON for each Property (like `"category": "SPG"` or `"state": "",`)
3. Parse the phone number to have a consistent, unique format when saved.