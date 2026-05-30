# E-Commerce R&D — WMS as Bangladesh E-Commerce Backend

**Date:** 2026-05-29  
**Scope:** Using WMS (NinjaBring/Packiyo) as the backend engine for a public-facing e-commerce website targeting Bangladesh.

---

## 1. What WMS Already Provides (Reuse As-Is)

| Capability | Status | Notes |
|-----------|--------|-------|
| Full order lifecycle | ✅ Ready | Ingestion → allocation → picking → packing → shipment |
| Pathao courier integration | ✅ Ready | Rates, labels, returns, drop-points, BDT hardcoded |
| Multi-location inventory | ✅ Ready | Warehouses, bins, lot tracking, quantity breakdown |
| Automation rules engine | ✅ Ready | Conditional triggers and actions on order events |
| JSON:API layer | ✅ Ready | Versioned, filterable; `publicv1` and `frontendv1` |
| Outbound webhooks | ✅ Ready | Fires on order, shipment, inventory, product events |
| Storefront API | ✅ Partial | `POST /storefront/orders`, `GET /storefront/products`, `GET /storefront/product_search` |
| Billing engine (3PL) | ✅ Ready | Rate cards, invoicing, DomPDF — adapt for customer invoices |
| Real-time (Ably) | ✅ Ready | Used for print jobs; re-purpose for order tracking updates |

---

## 2. Gap Analysis

### 2.1 Critical Gaps (Blockers for Go-Live)

#### Payment Status on Orders
- `Order` has `subtotal`, `total`, `tax`, `discount`, `payment_hold` but **no `payment_status`**.
- Stripe Cashier is installed but not linked to orders.
- **Action:** Add `payment_status` enum, `payment_method`, `payment_reference`, `paid_at`, COD-specific `cod_amount` columns to `orders`. Add a `Payment` model morphing to `Order`.

#### Bangladesh Payment Gateways
- Only Stripe Cashier exists — not usable by BD consumers.
- **Action:** Integrate in priority order:
  1. **bKash** — Payment Gateway API (majority of BD consumers)
  2. **Nagad** — Merchant API
  3. **SSLCOMMERZ** — Cards + bank transfer + all mobile wallets in one

#### More BD Couriers
- Only Pathao integrated; Steadfast and RedX are equal or larger in BD last-mile.
- **Action (in progress):** Steadfast → RedX → eCourier.

#### BD Address Model
- `ContactInformation` stores free-text addresses; Pathao requires `city_id`/`zone_id` integers.
- **Action:** Add `bd_city_id`, `bd_zone_id`, `bd_area_id` to `ContactInformation`. Seed Pathao city/zone data. Expose `GET /api/storefront/bd-locations/{cities|zones|areas}`.

#### COD Flow
- ~60–70% of BD e-commerce is Cash on Delivery.
- **Action:** Add `payment_method = 'cod'`, `cod_amount` on Order. Pass COD amount to Pathao/Steadfast during shipment creation.

### 2.2 Important Gaps

#### End-Customer (B2C) Identity
- `Customer` = business account; there is no shopper/end-customer model.
- **Action:** Add `EndCustomer` model (`name`, `email`, `phone`, `password_hash`, `email_verified_at`). Add `end_customer_id` (nullable) to `orders`. Auth endpoints under `/api/storefront/auth/`.

#### Storefront API Hardening
- Existing product endpoints return WMS-internal fields (location quantities, lot IDs, cost).
- **Action:** Create dedicated `StorefrontProductResource` exposing only public fields. Add `GET /api/storefront/products/{id}`, `GET /api/storefront/shipping-rates`, `GET /api/storefront/track/{tracking_number}`.

#### SMS Notifications
- Email only (`OrderShipped` mail class). BD customers expect SMS.
- **Action:** Integrate **SSL Wireless** (primary BD SMS) with Twilio as fallback. Fire SMS on order confirmed, shipped, delivered.

#### Inventory Sync Back to Frontend
- WMS pulls from frontend sources; stock changes in WMS never push back.
- **Action:** Fire `inventory.updated` outbound webhook on `LocationProduct` quantity change. Add `quantity_available` to storefront product response.

### 2.3 Product Catalog Improvements

| Missing | Action |
|---------|--------|
| Product categories | Add `Category` model with parent-child; `product_categories` pivot |
| SEO metadata | Add `slug`, `meta_title`, `meta_description` to `Product` |
| Rich description | Add `description` (longtext) and `short_description` |
| Compare-at price | Add `compare_at_price` for showing sale discount |
| Draft/published state | Add `is_published` boolean; only sync published to storefront |

### 2.4 Promotional Engine

- No coupon, discount, or flash sale system.
- **Action:** Add `Coupon` model (`code`, `type`: percent/fixed/free_shipping, `value`, `min_order_amount`, `usage_limit`, `used_count`, `expires_at`). Add `POST /api/storefront/coupons/apply`. Store `coupon_id` + `discount` on `Order` (discount field already exists).

---

## 3. Courier Integrations Roadmap

| Courier | Priority | API Style | Auth | Status |
|---------|----------|-----------|------|--------|
| Pathao | — | REST JSON | OAuth token | ✅ Done |
| **Steadfast** | P0 | REST JSON | API-Key + Secret-Key headers | 🔄 In Progress |
| RedX | P1 | REST JSON | API key header | Pending |
| eCourier | P2 | REST JSON | API key | Pending |
| Paperfly | P3 | REST JSON | API key | Pending |

---

## 4. Payment Gateways Roadmap

| Gateway | Priority | BD Coverage | Notes |
|---------|----------|------------|-------|
| bKash Payment Gateway | P0 | ~60% | Requires merchant account + IP whitelist |
| Nagad Merchant API | P0 | ~25% | Requires PGW agreement |
| SSLCOMMERZ | P1 | Cards + banks | Easiest to integrate, covers everything |
| Cash on Delivery | P0 | ~65% of orders | Flag only, no gateway needed |

---

## 5. Prioritised Build Order

### Phase 1 — Go-Live Blockers (Weeks 1–4)
1. Payment status columns on `orders` + COD flow
2. Steadfast courier integration ← **current sprint**
3. BD address model (city/zone seeding + storefront API)
4. Storefront API cleanup (dedicated resource, product detail, tracking endpoint)
5. Order confirmation + SMS notifications (SSL Wireless)

### Phase 2 — Customer Experience (Weeks 5–10)
6. bKash + Nagad payment integration
7. SSLCOMMERZ for cards
8. `EndCustomer` model + storefront auth (register/login/order history)
9. Product categories + SEO fields + `is_published`
10. Inventory sync-back webhook

### Phase 3 — Growth (Weeks 11–20)
11. Coupon/promo engine
12. RedX courier integration
13. Analytics dashboard (GMV, conversion, top products, delivery SLA)
14. Customer return-request API + refund flow
15. Mobile apps (React Native reusing storefront API)

---

## 6. Steadfast API Reference

**Base URL:** `https://portal.steadfast.com.bd/api/v1`

**Authentication:** Static headers on every request:
```
Api-Key: {api_key}
Secret-Key: {secret_key}
Content-Type: application/json
```

### Endpoints Used

| Method | Endpoint | Purpose |
|--------|---------|---------|
| POST | `/create_order` | Create single shipment |
| POST | `/create_order/bulk-order` | Create multiple shipments |
| GET | `/status_by_cid/{consignment_id}` | Track by consignment ID |
| GET | `/status_by_invoice/{invoice}` | Track by invoice/order number |
| POST | `/status_by_cid/bulk-check` | Bulk status check |
| POST | `/cancel_order/{consignment_id}` | Cancel a shipment |
| GET | `/get_balance` | Check account balance |

### Create Order Request Body
```json
{
  "invoice":           "ORDER-12345",
  "recipient_name":    "John Doe",
  "recipient_phone":   "01700000000",
  "recipient_address": "House 1, Road 2, Dhaka",
  "cod_amount":        500.00,
  "note":              "Fragile",
  "item_description":  "Clothing item"
}
```

### Create Order Response
```json
{
  "status": 200,
  "message": "Parcel Placed Successfully",
  "data": {
    "consignment_id": "ABCDEF1234",
    "invoice":        "ORDER-12345",
    "tracking_code":  "ABCDEF1234",
    "recipient_name": "John Doe",
    "cod_amount":     500.00,
    "status":         "In Review"
  }
}
```

---

## 7. Architecture Notes

### Shipping Provider Pattern
All carriers implement `App\Interfaces\BaseShippingProvider`:
- `getCarriers()` — creates `ShippingCarrier` + `ShippingMethod` records
- `ship()` — calls carrier API, creates `Shipment`, stores label + tracking
- `void()` — cancels shipment at carrier
- `return()` — creates reverse shipment
- `getShippingRates()` / `getCheapestShippingRates()` — rate quotes

Carrier is selected via `ShippingComponent::SHIPPING_CARRIERS` map keyed by `carrier_service` string on `ShippingCarrier`.

Credentials are stored per-carrier per-customer (e.g., `PathaoCredential`, `SteadfastCredential`) and linked to `ShippingCarrier` via polymorphic `credential()` relation.

### Storefront API
Entry points live under `/api/storefront/*` (no auth required for browsing; order creation can be tenant-keyed by domain/slug). These are separate from the authenticated JSON:API routes under `/api/v1/`.
