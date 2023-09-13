<?php
/*


https://www.gbif.org/developer/occurrence

{
  "creator": "userName",
  "notificationAddresses": [
    "userEmail@example.org"
  ],
  "sendNotification": true,
  "format": "SIMPLE_CSV",
  "predicate": {
    "type": "and",
    "predicates": [
      {
        "type": "equals",
        "key": "BASIS_OF_RECORD",
        "value": "PRESERVED_SPECIMEN"
      },
      {
        "type": "in",
        "key": "COUNTRY",
        "values": [
          "KW",
          "IQ",
          "IR"
        ]
      }
    ]
  }
}

curl --include --user userName:PASSWORD --header "Content-Type: application/json" --data @query.json https://api.gbif.org/v1/occurrence/download/request


*/