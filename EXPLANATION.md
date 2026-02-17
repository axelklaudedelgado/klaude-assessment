# Technical Explanation

This document explains key implementation decisions and technical approaches used in this Shopify integration service.

---

## 1. Why GraphQL Was Used

GraphQL was used because, as Shopify themselves have said, GraphQL unlocks the full potential of Shopify. It is flexible and scalable and its API is very easy to setup and understand. It is very straightforward. A few reasons why Shopify chose GraphQL as their definitive API include efficient data access, optimized data retrieval, strongly typed schema, and high class tooling and documentation. When it comes to raw performance, it is also stated that GraphQL outperforms REST by 75%. Overall, GraphQL is used because of its ease of use, efficiency, and it is officially chosen as the definitive API by Shopify.

**References:**

- https://www.shopify.com/ph/partners/blog/all-in-on-graphql

---

## 2. How OAuth Verification Works

OAuth verification specifically in Shopify works by following the authorization code grant flow. Under the hood, the flow involves installing the app, authorizing the requested scopes, and Shopify issuing an authorization code. The app then exchanges this code for an access token to authenticate API calls. This process also involves verification of the installation request via HMAC and validation of the shop domain name to finally get the access token with proper redirection.

**References:**

- https://shopify.dev/docs/apps/build/authentication-authorization
- https://shopify.dev/docs/apps/build/authentication-authorization/access-tokens/authorization-code-grant

---

## 3. How Webhook Verification Works

Webhook verification works by accessing the raw body to access the secret and subject it to HMAC verification. The signatures are compared by making sure what was received matches the expected HMAC signature. In my implementation, timing-safe comparison is also applied to prevent timing attacks wherein the attacker tries to exploit a system by analyzing the time taken to execute or get a response from encryption algorithms.

---

## 4. How Pagination Is Implemented

My pagination implementation involves cursor-based pagination as required by the examination. The way it works is the request includes a cursor which acts like a bookmark and a limit of the page size. Shopify returns items after the specified cursor plus an endCursor which specifies where the returned items end and what to use for the next request. Loop continues until hasNextPage is false.

**GraphQL query structure:**

```graphql
products(first: 50, after: $cursor) {
  edges {
    cursor    # Bookmark for each item
    node { ... }  # Actual data
  }
  pageInfo {
    hasNextPage   # Are there more items?
    endCursor     # Bookmark to use for next request
  }
}
```

---

## 5. How Rate Limiting Is Handled

The way my rate limiting is implemented references the production-ready and tested approaches being currently used in the SDK: https://github.com/Shopify/shopify-app-php

My implementation uses a reactive approach to handle Shopify's rate limits.

**Detection:**

The GraphQL service detects rate limiting in two ways:

- HTTP 429 status codes from the API
- GraphQL `THROTTLED` error codes in the response

**Retry Strategy:**

When rate limiting is detected, the service automatically retries the request with exponential backoff. The delay increases with each attempt (1 second, 2 seconds, 4 seconds) plus a small random jitter to prevent multiple requests from retrying simultaneously.

**Preventing Infinite Loops:**

I implemented a maximum retry limit of 2 attempts. After 2 failed retries, the request throws an exception rather than continuing indefinitely. This prevents the application from getting stuck in an endless retry loop.

**Implementation:**

```php
if ($statusCode === 429 && $attempt < $maxRetries) {
    sleep(2);  // Wait before retry
    $attempt++;
    continue;
}
```

The service also handles transient server errors (502, 503, 504) with the same retry logic, as these are temporary issues that often resolve on retry.

This reactive approach ensures the application respects Shopify's rate limits while maintaining reliability through automatic retries.

---

## 6. What Broke During Development and How I Fixed It

### Issue 1: SSL Certificate Verification Error

**Problem:** My local environment could not verify Shopify's SSL certificates and the error message said "unable to get local issuer certificate."

**Solution:** I downloaded the necessary CA certificates for Windows and added them to my local installation of PHP. Then I made sure to edit php.ini setup to point to these newly added certificates.

### Issue 2: Protected Customer Data Access

**Problem:** The app I was testing for the sync order service was not approved to access the Order object due to Shopify's protected customer data restrictions.

**Solution:** I referred to the provided documentation in the error message and configured the Protected Customer Data settings for my app. After that, I tested my service in PHP Tinker again and confirmed syncing now works.

### Issue 3: Webhook HMAC Verification Mismatch

**Problem:** When I tested the HMAC verification I did for the webhooks, it was mismatched so I investigated what was going on.

**Solution:** I added logging in my middleware and immediately checked if HMAC being received and compared are the same. I also tried to introduce a test localhost endpoint to just check connection with server. After confirming that the two HMACs do not match, I went to check the code again. Then, I went to check the .env and checked if my API key matches what was needed. Turns out, the API key for webhooks is a separate one from the store. After correcting this, everything worked properly again.

---

## 7. What Would Change for a Multi-Tenant Production Environment

In my current implementation, I already tried to introduce multi-tenant ready logic. The implementation involves accessing, saving, and working with the shop_domain across the app. This is because for a multi-tenant environment, the shops all have a different shop domain, and saving these separately for each tenant makes it so that multiple tenants are supported by making sure to remember each and every tenant's shop domain. In my OAuth, for example, each shop has its own encrypted access token being saved. The API endpoints also require shop_domain to identify which shop's data to sync.

For production multi-tenant, I would add **User Authentication and Authorization**. Currently, any request with a valid shop_domain can trigger syncs. In production, I would implement user accounts where users authenticate and can only access shops they own or manage. This would prevent unauthorized access and ensure proper data isolation between different customers.

---

## Development Notes

This project was developed with assistance from AI tools (Claude) for research, code pattern generation, and technical documentation. The developer also compared and contrasted code patterns generated by AI with the actual Shopify documentation and utilized the Shopify AI assistant, which provides references and patterns that follow the official documentation. The developer cross-referenced patterns from multiple sources to ensure accuracy and adherence to best practices. All code has been reviewed, tested, and is understood by the developer.
