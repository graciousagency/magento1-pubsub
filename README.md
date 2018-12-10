# Gracious Magento 1 Google Pub/Sub Module 

## functionality
* send orders via cron to pubsub 
* send orders via event to pubsub 

## fields which can be filled in the backend:
* orders_topic 
  * sets the pubsub topic name
* service_account
  * google cloud service account json   
* start_from_days
* end_from_days

## todo:
* newsletters subscribers
* customers
* quotes