# Vendor_ExtraFee

Adds a configurable extra fee for specific shipping or payment methods, restricted to
specific customer groups.

## What was built

- Admin config for two independent fees: **Shipping Method Fee** and **Payment Method Fee**.
- The fee shows up everywhere: shipping method list, payment method list, checkout
  totals, order/invoice/credit-memo view (frontend, guest, admin), confirmation emails,
  and the Sales REST/GraphQL API.
- The fee persists through the full order lifecycle (order → invoice → credit memo).
  Tested with real placed orders and database checks, not just unit tests.

## Configuration

**Admin → Stores → Configuration → Sales → Extra Fee**

| Section | Fields |
|---|---|
| Shipping Method Fee | Enabled, Fee Amount, Customer Groups (multiselect), Shipping Methods (multiselect) |
| Payment Method Fee | Enabled, Fee Amount, Customer Groups (multiselect), Payment Methods (multiselect) |

A fee applies when it's enabled, the customer's group matches, and the selected method
matches. All three, not just one.

## What each piece does

| File | Responsibility |
|---|---|
| `Model/FeeCalculator.php` | Decides if a fee applies and how much. Everything else calls this instead of duplicating the eligibility logic. |
| `Model/Total/Quote/ShippingFee.php` | Adds the shipping fee onto the native `shipping` total, so it's carried through order/invoice/credit memo by Magento's existing `shipping_amount` handling. No extra persistence code needed. |
| `Model/Total/Quote/PaymentFee.php` | Registers its own `extra_fee_payment` total line. Applies once per shipping address, same as how Magento charges shipping per address on multi-address orders. |
| `Model/Total/Invoice/PaymentFee.php`, `Model/Total/Creditmemo/PaymentFee.php` | Carry the payment fee from order → invoice → credit memo. Charged once, not split across partial invoices, and refunded up to the outstanding balance on credit memos. |
| `Observer/CopyFeeToOrder.php` | Copies the fee from the quote address onto the new order. `fieldset.xml` can't do this (see "Notable gotchas"). |
| `Plugin/AddFeeToShippingMethodAmount.php`, `Plugin/AppendPaymentFeeToTitle.php` | Show the fee in the shipping/payment method lists during checkout, before the customer picks one. |
| `Block/Sales/Order/PaymentFee.php` + `view/*/layout/*.xml` | Renders a "Payment Method Fee" row on order/invoice/credit-memo totals, on frontend, guest, admin, and email. |
| `etc/extension_attributes.xml` + `Plugin/AddExtraFeeToOrderExtensionAttributes.php` | Exposes the fee under `extension_attributes` in the Sales REST/GraphQL API. |
| `Helper/Config.php` | Reads admin config. Negative amounts are clamped to 0. |

## Notes

- **`Magento\Tax\Model\Sales\Total\Quote\Tax` (sort_order 450) overwrites the `shipping`
  total.** Any collector that adds to `shipping` has to run after it (this module uses
  sort_order 500), or the addition just disappears with no error.
- **`fieldset.xml` can't copy this field from quote to order.** `Address\ToOrder::convert()`
  populates the order through `DataObjectHelper::populateWithArray()`, which only keeps
  fields with a real, declared setter. `Order` only has a magic `__call()` setter for a
  plain custom column, so the value silently becomes `0`, no error. Known Magento
  limitation, not a bug in this module:
  [magento/magento2#5823](https://github.com/magento/magento2/issues/5823). Fixed with an
  explicit observer instead of relying on `fieldset.xml`.
- **Considered: a class preference on `Order` with real getter/setter methods.** Would fix
  the `fieldset.xml` problem at its root, since `get_class_methods()` would find real
  methods instead of only magic ones. Only one preference can exist per class
  across a Magento instance, and `Order` is one of the most commonly extended core
  classes. Plugins and observers compose across modules
- **Order-totals display blocks differ by context.** `order_totals`, `invoice_totals`,
  `creditmemo_totals` are three separate parent block names, each needing its own layout XML.

## Assumptions

- **One flat amount per fee type**, not a different amount per method or customer group.
  The spec describes a single `amount` field per fee type. A per-method/per-group amount
  grid would solve a problem the spec doesn't ask for.
- **The fee is charged once per shipping address**, not once per quote, to match how
  Magento's "Ship to Multiple Addresses" checkout charges shipping (one order per address,
  each charged independently).
- **The fee is a flat one-time charge, not prorated** across partial invoices/credit memos.
- **A negative configured amount is treated as 0**, never as a hidden discount.
- **Enabled + customer group + method are all required together.** There's no "OR" mode.

## Improvements with more time

- **Package it as a real installable extension.** Right now it only exists inside this
  Magento checkout's `app/code`. Splitting it into its own git repo, tagging releases,
  and adding it as a Composer dependency (via a private repository or Packagist) would
  let it be installed with `composer require` like any other module, instead of copied in.
- **CI pipeline.** A GitHub Actions workflow running `phpcs`, `phpunit`, and static
  analysis (PHPStan/PHPMD) on every push and PR, instead of relying on manually running
  them.
- **Fill remaining test gaps.** `Model/Total/Invoice/PaymentFee.php`,
  `Model/Total/Creditmemo/PaymentFee.php`, and `Block/Sales/Order/PaymentFee.php` have no
  unit tests yet. They were verified manually with real orders, invoices, and credit
  memos instead.
- **Get the integration tests actually running.** `Test/Integration/` exists but can't run
  in this environment (no `dev/tests/integration/etc/install-config-mysql.php` configured).
- **Per-method/per-customer-group amounts**, if that requirement ever comes up. See
  "Assumptions" above for what that would take: a repeatable grid, or a dedicated rule
  table for a full matrix.

## Running the tests

```bash
vendor/bin/phpcs --standard=Magento2 app/code/Vendor/ExtraFee
vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist app/code/Vendor/ExtraFee/Test/Unit
```

Integration tests (`Test/Integration/`) require a configured
`dev/tests/integration/etc/install-config-mysql.php`:

```bash
vendor/bin/phpunit -c dev/tests/integration/phpunit.xml.dist app/code/Vendor/ExtraFee/Test/Integration
```

