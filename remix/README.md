# Welcome to Remix + Vite!

📖 See the [Remix docs](https://remix.run/docs) and the [Remix Vite docs](https://remix.run/docs/en/main/future/vite) for details on supported features.

## Development

Run the Vite dev server:

```shellscript
npm run dev
```

## Deployment

First, build your app for production:

```sh
npm run build
```

Then run the app in production mode:

```sh
npm start
```

Now you'll need to pick a host to deploy it to.

### DIY

If you're familiar with deploying Node applications, the built-in Remix app server is production-ready.

Make sure to deploy the output of `npm run build`

-   `build/server`
-   `build/client`

## Run cypress with cucumber

Before run test:

set variables in /remix/.env

```
  CYPRESS_HOST_FRONTEND_PACKIYO=http://localhost
  CYPRESS_HOST_FRONTEND_PACKIYO_REMIX=http://localhost:4000
  CYPRESS_USER_EMAIL=email
  CYPRESS_USER_PASSWORD=password
```

root

```
cd docker && docker-compose up
```

root

```
cd remix && npm i && npm run dev
```

Run test

```
npx cypress open
```
