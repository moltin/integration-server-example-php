# PHP Example of an Integration Server

This repsository is a PHP demo application that shows what can be accomplished with the [moltin integrations](https://moltin.api-docs.io/v2/integrations) functionality.

When using integrations, you can choose receive notifications when observed events occur on your store. There are two types of integration that can then callback to this application - `webhooks` and `emails`. Both deliver the same payload to your application.

Webhooks notify you and simply require an HTTP 200 status code as a response. You can then perform any actions that are required in your application.

Emails on their own would not neccessarily notify you. However, if you have configured your email integration to get the contents from a remote URL then you will need to process the incoming notification and return the content so that the email can be sent.

If you take too long processing an incoming request, it will time out and we will try again in accordance with our retry policy.

## Starting the Server

Because this is just an example application, you can fire it up with:

```bash
php -S localhost:8000 -t ./src/public
```

Now your server is running and listening for requests on http://localhost:8000. For production you need to have your webserver exposed publicly on the internet but that is beyond the scope of this example application.

You should copy `./src/public/.env.template` to `./src/public/.env` and fill in the environment variables with your credentials. This allows you to make requests to the **moltin** API when incoming email webhooks are received if you want - for example, getting some related products to display in an email when an order is placed.

## Making Requests

This application is pretty much a small, compact API in that it receives and sends JSON.

There is a `resources` directory which contains a [PAW](https://paw.cloud/) file that you can use to mock your calls so that you can test the application behaviour before putting it into production.

## Webhooks

There is an included example of a Slack notification when a new order is placed on your store (triggered by a webhook).

To use this example, you will need to edit the following details in your `./src/.env` file:

```bash
SLACK_WEBHOOK=
SLACK_ICON=
SLACK_USERNAME="moltin"
SLACK_CHANNEL=""
SLACK_LINK_NAMES=true
```

First you will need to [generate the webhook URL](https://my.slack.com/services/new/incoming-webhook) and use the URL as the `SLACK_WEBHOOK` ENV variable. Then add the `SLACK_CHANNEL` (#channel or @user-name) you want to notify.

The icon and username can be customised.

To trigger this event for testing, you can make a `POST` request to `http://localhost:8000/webhook` with your secret key in the header and a json payload:

```json
{
  "id": "d7706829-f612-42a6-87b8-0aa1eb90f81c",
  "triggered_by": "order.created",
  "attempt": "1",
  "integration": {
    "id": "71679ff8-36c1-4f8f-8ed2-cea50550d78c",
    "integration_type": "webhook",
    "name": "Order Created",
    "description": "An example order created integration"
  },
  "resources": [
    {
      "type": "order",
      "id": "f32827d2-7f0a-47dc-a3a0-d9434e5a3ac8",
      "customer": {
        "name": "Customer Name",
        "email": "customer@domain.com"
      },
      "meta": {
        "value": {
          "with_tax": {
            "formatted": "$1,000,000.00"
          }
        },
        "counts": {
          "products": {
            "total": "7",
            "unique": "3"
          }
        }
      }
    }
  ]
}
```

## Email

There is an example email endpoint which, when notified of a new order, makes a call to the **moltin** API to get 10 products (sorted by name) which are then added to an order confirmation email.

You must ensure that the `.env` has at least the following variables set:

```bash
CLIENT_ID=
CLIENT_SECRET=
```

To trigger this event manually, make a `POST` request to `http://localhost:8000/email` with the following payload:

```json
{
  "id": "e743bf71-e14b-4e29-8de7-c23894c2f19a",
  "triggered_by": "order.created",
  "attempt": "1",
  "integration": {
    "id": "71679ff8-36c1-4f8f-8ed2-cea50550d78c",
    "integration_type": "email",
    "name": "Order Created",
    "description": "An example order created integration"
  },
  "resources": [
    {
      "type": "order",
      "id": "7228817c-f4de-460f-9a67-15210846873b",
      "customer": {
        "name": "Customer Name",
        "email": "customer@domain.com"
      },
      "meta": {
        "value": {
          "with_tax": {
            "formatted": "$1,000,000.00"
          }
        },
        "counts": {
          "products": {
            "total": "7",
            "unique": "3"
          }
        }
      }
    }
  ]
}
```

The example response must be followed. We will use the body you respond with to decide what to do with the email. For more information on the response, please [read the docs](https://moltin.api-docs.io/v2/integrations#responses).
