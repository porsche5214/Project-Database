import { authApi } from "../api/auth";
import { ordersApi, type ApiOrder } from "../api/orders";
import { FormEvent, ReactNode, useEffect, useMemo, useState } from "react";
import { useSearchParams } from "react-router-dom";
import { photos } from "../data/products";
import { cartItemKey, type CartItem, type MemberInfo, type Page, type Product } from "../types/shop";
import { Button, CheckoutField, Empty, Field, Icon, SectionTitle, Stars } from "../components/ui";
import type { ProductReview } from "../types/shop";
import ReviewsSection from "../components/ReviewsSection";

export function Home({ go, cards }: { go: (p: Page) => void; cards: ReactNode }) {
  const categories = ["Men", "Women", "Unisex", "T-Shirts", "Outerwear", "Accessories"];
  return <main>
    <section className="hero">
      <img src={photos[4]} alt="NaeMak monochrome streetwear campaign"/>
      <div className="hero-shade"/>
      <div className="hero-copy"><span className="eyebrow light">NAEMAK — NEW SEASON 2025</span><h1>BE BOLD.<br/>BE NAEMAK.</h1><p>Everyday style, made to stand out.</p><div className="hero-buttons"><Button variant="light" onClick={() => go("products")}>SHOP NOW</Button><Button variant="outline" onClick={() => go("products")}>EXPLORE COLLECTION</Button></div></div>
      <div className="hero-index">01 / 04</div>
    </section>
    <section className="marquee"><span>NEW FORMS</span><i/> <span>MADE IN BANGKOK</span><i/> <span>EVERYDAY ICONS</span><i/> <span>UNCOMPROMISED FIT</span></section>
    <section className="section">
      <SectionTitle kicker="SHOP BY" title="CATEGORIES" action="VIEW ALL" onAction={() => go("products")}/>
      <div className="category-grid">{categories.map((c, i) => <button className={`category-card c${i}`} key={c} onClick={() => go("products")}><img src={photos[(i + 1) % photos.length]} alt=""/><span>{c}</span><i><Icon name="arrow"/></i></button>)}</div>
    </section>
    <section className="section product-section">
      <SectionTitle kicker="THE EDIT" title="FEATURED PIECES" action="SHOP ALL" onAction={() => go("products")}/>
      <div className="product-grid">{cards}</div>
    </section>
    <section className="campaign">
      <div className="campaign-copy"><span className="eyebrow light">NAEMAK EDITORIAL / 02</span><h2>FIND YOUR<br/>FORM.</h2><p>Designed for the city. Built for your every day.</p><Button variant="light" onClick={() => go("products")}>SHOP COLLECTION</Button></div>
      <img src={photos[2]} alt="NaeMak Find Your Form campaign"/>
    </section>
  </main>;
}

export function ProductsPage({ products, renderCards, query, setQuery }: { products: Product[]; renderCards: (items: Product[]) => ReactNode[]; query: string; setQuery: (v: string) => void }) {
  const [filters, setFilters] = useState(false);
  const [categories, setCategories] = useState<string[]>([]);
  const [genders, setGenders] = useState<string[]>([]);
  const [brands, setBrands] = useState<string[]>([]);
  const [sizes, setSizes] = useState<string[]>([]);
  const [colors, setColors] = useState<string[]>([]);
  const [inStockOnly, setInStockOnly] = useState(false);
  // Filter options come from the products the API returned, so every option can match something.
  const options = useMemo(() => {
    const unique = (values: string[]) => [...new Set(values)].sort((a, b) => a.localeCompare(b));
    const sizeRank = (size: string) => { const i = SIZE_ORDER.indexOf(size); return i === -1 ? 100 + (Number(size) || 0) : i; };
    return {
      categories: unique(products.map((p) => p.category)),
      genders: GENDER_OPTIONS.filter((g) => products.some((p) => p.gender === g.value)),
      brands: unique(products.map((p) => p.brand)),
      sizes: [...new Set(products.flatMap((p) => p.sizes))].sort((a, b) => sizeRank(a) - sizeRank(b) || a.localeCompare(b)),
      colors: unique(products.flatMap((p) => p.colors)),
      priceCap: Math.max(1000, Math.ceil(Math.max(0, ...products.map((p) => p.price)) / 500) * 500),
    };
  }, [products]);
  const [maxPrice, setMaxPrice] = useState<number | null>(null);
  const priceLimit = maxPrice ?? options.priceCap;
  const [sort, setSort] = useState("recommended");
  const toggle = (value: string, selected: string[], update: (values: string[]) => void) => update(selected.includes(value) ? selected.filter((item) => item !== value) : [...selected, value]);
  const visibleProducts = useMemo(() => {
    const matching = products.filter((product) => {
      const categoryMatch = !categories.length || categories.includes(product.category);
      // Unisex items show up under Men and Women too.
      const genderMatch = !genders.length || genders.includes(product.gender) || product.gender === "unisex";
      const brandMatch = !brands.length || brands.includes(product.brand);
      const sizeMatch = !sizes.length || sizes.some((size) => product.sizes.includes(size));
      const colorMatch = !colors.length || colors.some((color) => product.colors.includes(color));
      const stockMatch = !inStockOnly || product.in_stock !== false;
      return categoryMatch && genderMatch && brandMatch && sizeMatch && colorMatch && stockMatch && product.price <= priceLimit;
    });
    return [...matching].sort((a, b) => {
      if (sort === "newest") return b.id - a.id;
      if (sort === "price-asc") return a.price - b.price;
      if (sort === "price-desc") return b.price - a.price;
      if (sort === "rating") return b.rating - a.rating;
      return 0;
    });
  }, [brands, categories, colors, genders, inStockOnly, priceLimit, products, sizes, sort]);
  const activeFilters = categories.length + genders.length + brands.length + sizes.length + colors.length + (inStockOnly ? 1 : 0) + (maxPrice !== null ? 1 : 0);
  const clearFilters = () => {
    setQuery("");
    setCategories([]);
    setGenders([]);
    setBrands([]);
    setSizes([]);
    setColors([]);
    setInStockOnly(false);
    setMaxPrice(null);
    setSort("recommended");
  };
  const cards = renderCards(visibleProducts);
  return <main className="listing-page">
    <div className="listing-head"><span className="eyebrow">NAEMAK COLLECTION</span><h1>{query ? `RESULTS FOR “${query.toUpperCase()}”` : "ALL PRODUCTS"}</h1><p>{cards.length} pieces — designed for wherever you go.</p></div>
    <div className="listing-toolbar"><button className="filter-toggle" onClick={() => setFilters(!filters)} aria-expanded={filters}><Icon name="filter"/> FILTERS</button><span>{cards.length} PRODUCTS</span><label>SORT BY <select value={sort} onChange={(event) => setSort(event.target.value)}><option value="recommended">Recommended</option><option value="newest">Newest</option><option value="price-asc">Price: Low to High</option><option value="price-desc">Price: High to Low</option><option value="rating">Highest Rated</option></select></label></div>
    <div className="listing-layout">
      <aside className={filters ? "filters open" : "filters"}>
        {query && <button className="clear-search" onClick={() => setQuery("")}>CLEAR SEARCH ×</button>}
        {activeFilters > 0 && <button className="clear-search" onClick={clearFilters}>CLEAR FILTERS ({activeFilters}) ×</button>}
        <Filter title="CATEGORY" options={options.categories} selected={categories} onToggle={(value) => toggle(value, categories, setCategories)}/>
        <Filter title="GENDER" options={options.genders.map((g) => g.value)} label={(value) => GENDER_OPTIONS.find((g) => g.value === value)?.label ?? value} selected={genders} onToggle={(value) => toggle(value, genders, setGenders)}/>
        <Filter title="BRAND" options={options.brands} selected={brands} onToggle={(value) => toggle(value, brands, setBrands)}/>
        <Filter title="SIZE" options={options.sizes} selected={sizes} onToggle={(value) => toggle(value, sizes, setSizes)} chips/>
        <div className="filter-group"><strong>COLOR</strong><div className="swatches">{options.colors.map((color) => <button key={color} className={`${color.toLowerCase()} ${colors.includes(color) ? "selected" : ""}`} onClick={() => toggle(color, colors, setColors)} aria-label={color} title={color} aria-pressed={colors.includes(color)}/>)}</div>{colors.length > 0 && <small>{colors.join(", ")}</small>}</div>
        <div className="filter-group"><strong>PRICE</strong><div className="price-inputs"><span>฿ 0</span><span>UP TO ฿ {priceLimit.toLocaleString()}</span></div><input type="range" min="0" max={options.priceCap} step="100" value={priceLimit} onChange={(event) => setMaxPrice(Number(event.target.value))} aria-label="Maximum price"/></div>
        <div className="filter-group"><strong>AVAILABILITY</strong><div className="filter-options"><label><input type="checkbox" checked={inStockOnly} onChange={(event) => setInStockOnly(event.target.checked)}/><span>In stock only</span></label></div></div>
      </aside>
      {cards.length ? <div className="product-grid listing-grid">{cards}</div> : <Empty title="No products found." text="Try a different name, brand, or category." action="CLEAR FILTERS" onAction={clearFilters}/>}
    </div>
  </main>;
}

// Progress of the latest order, driven by its real status.
// What was ordered, where it ships, and what the member can do next.
function OrderDetails({ order, onConfirmReceived, onViewProduct }: { order: ApiOrder; onConfirmReceived?: () => void; onViewProduct: (productId: number) => void }) {
  const subtotal = order.items.reduce((sum, item) => sum + Number(item.line_total), 0);
  const shipping = Math.max(0, Number(order.total_amount) - subtotal);
  const completed = order.status === "completed";
  return <div className="order-details">
    <OrderTimeline status={order.status}/>
    {order.status === "shipped" && onConfirmReceived && <div className="order-notice"><p>Your order is on its way. Received it? Confirm delivery to unlock reviews.</p><Button onClick={onConfirmReceived}>MARK AS RECEIVED</Button></div>}
    {completed && <div className="order-notice"><p>Order complete. You can now review the items below.</p></div>}
    {(order.status === "pending" || order.status === "paid") && <div className="order-notice"><p>Reviews open after the order is delivered and marked as received.</p></div>}
    <div className="order-items">{order.items.map((item) => <div className="order-item" key={item.variant_id}>
      <img src={item.image} alt={item.name}/>
      <div><span className="eyebrow">{item.brand}</span><strong>{item.name}</strong><span>{item.color} / {item.size} · QTY {item.quantity} × ฿{Number(item.unit_price).toLocaleString()}</span></div>
      <b>฿{Number(item.line_total).toLocaleString()}</b>
      <button onClick={() => onViewProduct(item.product_id)}>{completed ? "WRITE A REVIEW" : "VIEW PRODUCT"} <Icon name="arrow"/></button>
    </div>)}</div>
    <div className="order-meta">
      <div><span>SHIP TO</span><strong>{order.ship_name}</strong><p>{order.ship_address}</p><p>{order.ship_phone}</p></div>
      <div><span>CONTACT</span><p>{order.contact_email}</p></div>
      <div className="order-totals"><span>PAYMENT SUMMARY</span><p><em>Subtotal</em><b>฿{subtotal.toLocaleString()}</b></p><p><em>Shipping</em><b>{shipping ? `฿${shipping.toLocaleString()}` : "FREE"}</b></p><p className="grand"><em>Total</em><b>฿{Number(order.total_amount).toLocaleString()}</b></p></div>
    </div>
  </div>;
}

function OrderTimeline({ status }: { status: string }) {
  if (status === "cancelled") {
    return <div className="timeline"><div className="done"><i><Icon name="check"/></i><strong>ORDER CANCELLED</strong><span>STOCK RETURNED</span></div></div>;
  }
  const steps = [["pending", "ORDER PLACED"], ["paid", "PAID"], ["shipped", "SHIPPED"], ["completed", "COMPLETED"]];
  const current = steps.findIndex(([key]) => key === status);
  return <div className="timeline">{steps.map(([key, label], index) => <div className={index <= current ? "done" : ""} key={key}><i>{index <= current ? <Icon name="check"/> : index + 1}</i><strong>{label}</strong><span>{index <= current ? "DONE" : "PENDING"}</span></div>)}</div>;
}

const SIZE_ORDER = ["XS", "S", "M", "L", "XL", "XXL", "Free"];
const GENDER_OPTIONS = [{ value: "male", label: "Men" }, { value: "female", label: "Women" }, { value: "unisex", label: "Unisex" }];

export function Filter({ title, options, selected, onToggle, chips = false, label = (x) => x }: { title: string; options: string[]; selected: string[]; onToggle: (value: string) => void; chips?: boolean; label?: (value: string) => string }) {
  return <div className="filter-group"><strong>{title}</strong><div className={chips ? "filter-chips" : "filter-options"}>{options.map((x) => chips ? <button key={x} className={selected.includes(x) ? "selected" : ""} onClick={() => onToggle(x)} aria-pressed={selected.includes(x)}>{label(x)}</button> : <label key={x}><input type="checkbox" checked={selected.includes(x)} onChange={() => onToggle(x)}/><span>{label(x)}</span></label>)}</div></div>;
}

type StockVariant = { color: string; size: string; stock: number };

export function ProductDetail({ product, onAdd, onWish, go, loggedIn, reviews, onSubmitReview }: { product: Product & { variants?: StockVariant[] }; onAdd: (color: string, size: string, quantity: number) => void; onWish: () => void; go: (p: Page) => void; loggedIn: boolean; reviews: ProductReview[]; onSubmitReview: (review: Pick<ProductReview, "title" | "text" | "rating">) => Promise<boolean> }) {
  const variants = product.variants ?? [];
  // Without variant data we can't tell, so assume in stock (the server still rejects over-stock adds).
  const hasStockInfo = variants.length > 0;
  const stockOf = (match: (v: StockVariant) => boolean) => variants.filter(match).reduce((sum, v) => sum + v.stock, 0);
  const sizeStock = (c: string, s: string) => hasStockInfo ? stockOf((v) => v.color === c && v.size === s) : Infinity;
  const colorStock = (c: string) => hasStockInfo ? stockOf((v) => v.color === c) : Infinity;
  const soldOut = product.in_stock === false || (hasStockInfo && stockOf(() => true) === 0);
  // Start on a color that still has stock.
  const [color, setColor] = useState(product.colors.find((c) => colorStock(c) > 0) ?? product.colors[0]);
  const [size, setSize] = useState("");
  const [qty, setQty] = useState(1);
  const [notice, setNotice] = useState("");
  const available = size ? sizeStock(color, size) : colorStock(color);
  const canAdd = !soldOut && available > 0 && qty <= available;
  const chooseColor = (c: string) => {
    setColor(c);
    // Drop a size that isn't available in the new color.
    if (size && sizeStock(c, size) <= 0) setSize("");
    setQty(1);
  };
  const chooseSize = (s: string) => { setSize(s); setNotice(""); setQty((q) => Math.max(1, Math.min(q, sizeStock(color, s)))); };
  const add = () => { if (!size) { setNotice("Please select a size."); return; } if (!canAdd) return; onAdd(color, size, qty); };
  const stockText = soldOut ? "SOLD OUT"
    : available <= 0 ? "SOLD OUT IN THIS COLOR"
    : size && available <= 5 ? `ONLY ${available} LEFT — ORDER SOON`
    : "IN STOCK — READY TO SHIP";
  return <main className="detail-page">
    <div className="breadcrumbs">HOME / PRODUCTS / {product.category.toUpperCase()} / <b>{product.name.toUpperCase()}</b></div>
    <div className="detail-grid">
      <div className="gallery"><div className="thumbs">{[0,1,2].map((x) => <button key={x}><img src={product.image} alt=""/></button>)}</div><div className="main-image"><img src={product.image} alt={product.name}/><span>HOVER TO ZOOM</span></div></div>
      <div className="detail-info">
        <span className="eyebrow">{product.brand}</span><h1>{product.name}</h1>
        <div className="detail-rating"><Stars value={product.rating}/><span>{reviews.length} REVIEWS</span></div>
        <div className="detail-price">฿{product.price.toLocaleString()}</div>
        <p className="description">A considered essential made for everyday movement. Cut in a relaxed silhouette from premium structured fabric, balancing clean form with effortless comfort.</p>
        <div className="option"><div><b>COLOR</b><span>{color}</span></div><div className="color-options">{product.colors.map((c) => <button key={c} className={`${c.toLowerCase()} ${color === c ? "selected" : ""} ${colorStock(c) <= 0 ? "unavailable" : ""}`} onClick={() => chooseColor(c)} aria-label={colorStock(c) <= 0 ? `${c} (sold out)` : c} title={colorStock(c) <= 0 ? `${c} — sold out` : c}/>)}</div></div>
        <div className="option"><div><b>SELECT SIZE</b><button className="size-guide">SIZE GUIDE</button></div><div className="size-options">{product.sizes.map((s) => { const out = sizeStock(color, s) <= 0; return <button key={s} className={size === s ? "selected" : ""} disabled={out} title={out ? "Sold out" : undefined} onClick={() => chooseSize(s)}>{s}</button>; })}</div>{notice && <p className="validation">{notice}</p>}</div>
        <p className={`stock ${available <= 0 || soldOut ? "out" : ""}`}><i/> {stockText}</p>
        <div className="buy-row"><div className="quantity"><button onClick={() => setQty(Math.max(1, qty - 1))} aria-label="Decrease quantity">−</button><span>{qty}</span><button onClick={() => setQty(Math.min(qty + 1, size ? available : qty + 1))} disabled={!!size && qty >= available} aria-label="Increase quantity">+</button></div><Button onClick={add} disabled={!canAdd}>{soldOut || available <= 0 ? "SOLD OUT" : `ADD TO BAG — ฿${(product.price * qty).toLocaleString()}`}</Button></div>
        <Button variant="outline" onClick={onWish}><Icon name="heart"/> ADD TO WISHLIST</Button>
        <div className="detail-notes"><span>COMPLIMENTARY DELIVERY<br/><small>On all orders over ฿2,500</small></span><span>EASY RETURNS<br/><small>Within 14 days of delivery</small></span></div>
      </div>
    </div>
    <ReviewsSection product={product} reviews={reviews} loggedIn={loggedIn} onLogin={() => go("login")} onOrders={() => go("orders")} onSubmit={onSubmitReview}/>
  </main>;
}

export function CartPage({ items, updateQty, remove, go }: { items: CartItem[]; updateQty: (key: string, d: number) => void; remove: (key: string) => void; go: (p: Page) => void }) {
  const subtotal = items.reduce((s, x) => s + x.product.price * x.qty, 0);
  if (!items.length) return <main><Empty title="Your cart is empty." text="Your next favorite piece is waiting." action="CONTINUE SHOPPING" onAction={() => go("products")}/></main>;
  return <main className="cart-page"><div className="page-heading"><span className="eyebrow">YOUR SELECTION</span><h1>SHOPPING CART</h1></div><div className="cart-layout"><div className="cart-items"><div className="cart-labels"><span>PRODUCT</span><span>QUANTITY</span><span>SUBTOTAL</span></div>{items.map((x) => { const key = cartItemKey(x); return <article className="cart-item" key={key}><img src={x.product.image} alt={x.product.name}/><div className="cart-product"><span className="eyebrow">{x.product.brand}</span><strong>{x.product.name}</strong><span>{x.color} / {x.size}</span><button onClick={() => remove(key)}>REMOVE</button></div><div className="quantity"><button onClick={() => updateQty(key, -1)} aria-label={`Decrease ${x.product.name} quantity`}>−</button><span>{x.qty}</span><button onClick={() => updateQty(key, 1)} aria-label={`Increase ${x.product.name} quantity`}>+</button></div><b>฿{(x.product.price * x.qty).toLocaleString()}</b></article>; })}</div><Summary subtotal={subtotal} action="PROCEED TO CHECKOUT" onAction={() => go("checkout")}/></div></main>;
}

export function Summary({ subtotal, action, onAction, actionType = "button" }: { subtotal: number; action: string; onAction?: () => void; actionType?: "button" | "submit" }) {
  const shipping = subtotal >= 2500 ? 0 : 150;
  return <aside className="summary"><h2>ORDER SUMMARY</h2><div><span>Subtotal</span><b>฿{subtotal.toLocaleString()}</b></div><div><span>Shipping</span><b>{shipping ? `฿${shipping}` : "COMPLIMENTARY"}</b></div><div className="summary-total"><span>TOTAL</span><strong>฿{(subtotal + shipping).toLocaleString()}</strong></div><Button type={actionType} onClick={onAction}>{action}</Button><p>SECURE CHECKOUT · EASY RETURNS</p></aside>;
}

export function Checkout({ items, go, loggedIn, memberInfo, onSaveInfo, onPlaceOrder }: { items: { product: Product; qty: number; color: string; size: string }[]; go: (p: Page) => void; loggedIn: boolean; memberInfo: MemberInfo; onSaveInfo: (info: MemberInfo) => void; onPlaceOrder?: (info: MemberInfo, payment: string, saveAddress: boolean) => Promise<boolean | void> }) {
  const subtotal = items.reduce((s, x) => s + x.product.price * x.qty, 0);
  const emptyInfo: MemberInfo = { email:"", phone:"", firstName:"", lastName:"", address:"", city:"", province:"", postalCode:"", country:"Thailand" };
  const [info, setInfo] = useState<MemberInfo>(loggedIn ? memberInfo : emptyInfo);
  const [payment, setPayment] = useState("card");
  const [saveAddress, setSaveAddress] = useState(loggedIn);
  const update = (key: keyof MemberInfo, value: string) => setInfo((current) => ({...current, [key]: value}));
  const [submitting, setSubmitting] = useState(false);
  const [checkoutError, setCheckoutError] = useState("");
  const placeOrder = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (submitting) return;
    setCheckoutError("");
    if (onPlaceOrder) {
      setSubmitting(true);
      try {
        await onPlaceOrder(info, payment, saveAddress);
      } catch (err: any) {
        setCheckoutError(err.getFirstError ? err.getFirstError() : err.message || "Checkout failed");
      } finally {
        setSubmitting(false);
      }
    } else {
      if (loggedIn && saveAddress) onSaveInfo(info);
      go("confirmed");
    }
  };
  return <main className="checkout-page"><div className="page-heading"><span className="eyebrow">{loggedIn ? "MEMBER CHECKOUT" : "GUEST CHECKOUT"}</span><h1>CHECKOUT</h1></div><form className="checkout-layout" onSubmit={placeOrder}><div>
    {checkoutError && <p className="validation" style={{color: "var(--color-primary, red)", marginBottom: "1rem"}}>{checkoutError}</p>}
    <section className="checkout-block"><div className="block-title"><span>01</span><h2>CONTACT INFORMATION</h2></div><div className="checkout-form two-inputs"><CheckoutField label="EMAIL" type="email" value={info.email} onChange={(v) => update("email", v)}/><CheckoutField label="PHONE NUMBER" value={info.phone} onChange={(v) => update("phone", v)}/></div></section>
    <section className="checkout-block"><div className="block-title"><span>02</span><h2>SHIPPING INFORMATION</h2></div><div className="checkout-form"><div className="two-inputs"><CheckoutField label="FIRST NAME" value={info.firstName} onChange={(v) => update("firstName", v)}/><CheckoutField label="LAST NAME" value={info.lastName} onChange={(v) => update("lastName", v)}/></div><CheckoutField label="ADDRESS" value={info.address} onChange={(v) => update("address", v)}/><div className="two-inputs"><CheckoutField label="CITY / DISTRICT" value={info.city} onChange={(v) => update("city", v)}/><CheckoutField label="PROVINCE / STATE" value={info.province} onChange={(v) => update("province", v)}/></div><div className="two-inputs"><CheckoutField label="POSTAL CODE" value={info.postalCode} onChange={(v) => update("postalCode", v)}/><CheckoutField label="COUNTRY" value={info.country} onChange={(v) => update("country", v)}/></div>{loggedIn && <label className="save-address"><input type="checkbox" checked={saveAddress} onChange={(e) => setSaveAddress(e.target.checked)}/> Save this address for future orders</label>}</div></section>
    <section className="checkout-block"><div className="block-title"><span>03</span><h2>PAYMENT METHOD</h2></div><div className="payment-methods">{[["card","Credit / Debit Card","Visa, Mastercard, JCB"],["promptpay","PromptPay","Pay securely via QR code"],["transfer","Bank Transfer","Transfer from your bank account"],["cod","Cash on Delivery","Pay when your order arrives"]].map(([id,title,description]) => <label className={payment === id ? "selected" : ""} key={id}><input type="radio" name="payment" value={id} checked={payment === id} onChange={() => setPayment(id)}/><i/><span><strong>{title}</strong><small>{description}</small></span></label>)}</div></section>
    <section className="checkout-block"><div className="block-title"><span>04</span><h2>ORDER ITEMS</h2></div>{items.map((x) => <div className="checkout-item" key={cartItemKey(x)}><img src={x.product.image} alt={x.product.name}/><div><span className="eyebrow">{x.product.brand}</span><strong>{x.product.name}</strong><span>{x.color} / {x.size} · QTY {x.qty}</span></div><b>฿{(x.product.price * x.qty).toLocaleString()}</b></div>)}</section>
  </div><Summary subtotal={subtotal} action="PLACE ORDER" actionType="submit"/></form></main>;
}

export function Auth({ mode, go, onSuccess, message }: { mode: "login" | "register"; go: (p: Page) => void; onSuccess: () => void; message?: string }) {
  const [errorText, setErrorText] = useState("");
  const [loading, setLoading] = useState(false);

  const submit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setErrorText("");
    const formData = new FormData(event.currentTarget);

    if (mode === "register") {
      const confirmation = event.currentTarget.elements.namedItem("confirmPassword") as HTMLInputElement;
      const matches = formData.get("password") === formData.get("confirmPassword");
      confirmation.setCustomValidity(matches ? "" : "Passwords must match.");
      if (!matches) { confirmation.reportValidity(); return; }

      setLoading(true);
      try {
        await authApi.register({
          username: String(formData.get("username") || ""),
          email: String(formData.get("email") || ""),
          password: String(formData.get("password") || ""),
          first_name: String(formData.get("firstName") || ""),
          last_name: String(formData.get("lastName") || ""),
          phone: String(formData.get("phone") || ""),
        });
        onSuccess();
      } catch (err: any) {
        setErrorText(err.getFirstError ? err.getFirstError() : err.message || "Registration failed");
      } finally {
        setLoading(false);
      }
      return;
    }

    if (mode === "login") {
      setLoading(true);
      try {
        await authApi.login({
          login: String(formData.get("username") || ""),
          password: String(formData.get("password") || ""),
        });
        onSuccess();
      } catch (err: any) {
        setErrorText(err.getFirstError ? err.getFirstError() : err.message || "Invalid credentials");
      } finally {
        setLoading(false);
      }
    }
  };

  return <main className="auth-page"><div className="auth-image"><img src={photos[7]} alt="NaeMak campaign"/><span>BE BOLD.<br/>BE NAEMAK.</span></div><form className={`auth-form auth-form-${mode}`} onSubmit={submit}><span className="eyebrow">NAEMAK MEMBERS</span><h1>{mode === "login" ? "WELCOME BACK" : "CREATE ACCOUNT"}</h1><p>{errorText || message || (mode === "login" ? "Log in to access your wardrobe, orders and wishlist." : "Join NaeMak for a more personal shopping experience.")}</p>{mode === "register" && <div className="two-inputs"><Field label="FIRST NAME" name="firstName"/><Field label="LAST NAME" name="lastName"/></div>}<Field label={mode === "login" ? "USERNAME OR EMAIL" : "USERNAME"} name="username" type="text"/>{mode === "register" && <Field label="EMAIL" name="email" type="email"/>}<Field label="PASSWORD" name="password" type="password"/>{mode === "register" && <><Field label="CONFIRM PASSWORD" name="confirmPassword" type="password"/><Field label="PHONE" name="phone"/></>}<div className="auth-extra">{mode === "login" && <label><input type="checkbox"/> Remember me</label>}<button type="button">{mode === "login" ? "Forgot Password?" : "Privacy policy"}</button></div><Button type="submit" disabled={loading}>{mode === "login" ? "LOG IN" : "CREATE ACCOUNT"}</Button><div className="auth-switch">{mode === "login" ? "NEW TO NAEMAK?" : "ALREADY HAVE AN ACCOUNT?"}<button type="button" onClick={() => go(mode === "login" ? "register" : "login")}>{mode === "login" ? "CREATE AN ACCOUNT" : "LOG IN"}</button></div></form></main>;
}

export function Account({ memberInfo, go, onSaveInfo, onLogout, onViewProduct }: { memberInfo: MemberInfo; go: (page: Page) => void; onSaveInfo: (info: MemberInfo) => void; onLogout: () => void; onViewProduct: (productId: number) => void }) {
  // /account?tab=orders opens Order History directly (used by "View my orders" links).
  const [searchParams] = useSearchParams();
  const [tab, setTab] = useState<"Profile" | "Orders">(searchParams.get("tab") === "orders" ? "Orders" : "Profile");
  useEffect(() => { if (searchParams.get("tab") === "orders") setTab("Orders"); }, [searchParams]);
  const [openOrder, setOpenOrder] = useState<number | null>(null);
  const [orders, setOrders] = useState<ApiOrder[]>([]);
  const [ordersState, setOrdersState] = useState<"idle" | "loading" | "ready" | "error">("idle");
  const [ordersError, setOrdersError] = useState("");
  useEffect(() => {
    if (tab !== "Orders") return;
    setOrdersState("loading");
    ordersApi.list()
      .then((list) => { setOrders(list || []); setOrdersState("ready"); })
      .catch((err: any) => { setOrdersError(err?.message || "Could not load your orders."); setOrdersState("error"); });
  }, [tab]);
  const confirmReceived = async (orderId: number) => {
    setOrdersError("");
    try {
      const updated = await ordersApi.confirmReceived(orderId);
      setOrders((current) => current.map((o) => o.order_id === orderId ? updated : o));
    } catch (err: any) {
      setOrdersError(err?.getFirstError ? err.getFirstError() : err?.message || "Could not update this order.");
    }
  };
  const [editing, setEditing] = useState(false);
  const [info, setInfo] = useState(memberInfo);
  // While not editing, always show the saved profile (also undoes edits on CANCEL).
  useEffect(() => { if (!editing) setInfo(memberInfo); }, [memberInfo, editing]);
  const update =(key: keyof MemberInfo, value: string) => setInfo((current) => ({...current, [key]: value}));
  return <main className="account-page">
    <div className="account-hero"><span className="eyebrow">MY ACCOUNT</span><h1>HELLO, {(memberInfo.firstName || "MEMBER").toUpperCase()}.</h1></div>
    <div className="account-layout">
      <aside>
        {(["Profile","Orders"] as const).map((item) => <button className={tab === item ? "active" : ""} onClick={() => setTab(item)} key={item}>{item === "Orders" ? "Order History" : "My Profile"}<Icon name="chevron"/></button>)}
        <button onClick={() => go("wishlist")}>Wishlist<Icon name="chevron"/></button>
        <button onClick={onLogout}>Logout<Icon name="arrow"/></button>
      </aside>
      {tab === "Profile" && <section className="profile-panel"><div className="section-title"><div><span className="eyebrow">PERSONAL DETAILS</span><h2>MY PROFILE</h2></div><button onClick={() => setEditing(!editing)}>{editing ? "CANCEL" : "EDIT PROFILE"}</button></div><div className={`profile-form ${editing ? "editing" : "locked"}`}><div className="two-inputs"><CheckoutField label="FIRST NAME" readOnly={!editing} value={info.firstName} onChange={(value) => update("firstName",value)}/><CheckoutField label="LAST NAME" readOnly={!editing} value={info.lastName} onChange={(value) => update("lastName",value)}/></div><CheckoutField label="EMAIL" type="email" readOnly={!editing} value={info.email} onChange={(value) => update("email",value)}/><CheckoutField label="PHONE NUMBER" readOnly={!editing} value={info.phone} onChange={(value) => update("phone",value)}/><CheckoutField label="SAVED ADDRESS" readOnly={!editing} value={info.address} onChange={(value) => update("address",value)}/><div className="two-inputs"><CheckoutField label="CITY / DISTRICT" readOnly={!editing} value={info.city} onChange={(value) => update("city",value)}/><CheckoutField label="PROVINCE / STATE" readOnly={!editing} value={info.province} onChange={(value) => update("province",value)}/></div><div className="two-inputs"><CheckoutField label="POSTAL CODE" readOnly={!editing} value={info.postalCode} onChange={(value) => update("postalCode",value)}/><CheckoutField label="COUNTRY" readOnly={!editing} value={info.country} onChange={(value) => update("country",value)}/></div>{editing && <Button onClick={() => { onSaveInfo(info); setEditing(false); }}>SAVE CHANGES</Button>}</div></section>}
      {tab === "Orders" && <section className="orders"><div className="section-title"><div><span className="eyebrow">PURCHASES</span><h2>ORDER HISTORY</h2></div></div>{ordersError && <p className="validation">{ordersError}</p>}{ordersState === "loading" && <p className="eyebrow">LOADING ORDERS…</p>}{ordersState === "ready" && !orders.length && <Empty title="No orders yet." text="Your orders will appear here after checkout." action="START SHOPPING" onAction={() => go("products")}/>}{orders.map((ord) => { const open = openOrder === ord.order_id; return <div className="order-entry" key={ord.order_id}><article className="order-card"><div><span>ORDER</span><strong>#{ord.order_number}</strong></div><div><span>DATE</span><strong>{ord.formatted_date || ord.order_date}</strong></div><div><span>TOTAL</span><strong>฿{Number(ord.total_amount).toLocaleString()}</strong></div><div><span>STATUS</span><i className="status">{String(ord.status).toUpperCase()}</i></div><button onClick={() => setOpenOrder(open ? null : ord.order_id)} aria-expanded={open}>{open ? "HIDE DETAILS" : "VIEW DETAILS"} <Icon name={open ? "close" : "arrow"}/></button></article>{open && <OrderDetails order={ord} onConfirmReceived={() => confirmReceived(ord.order_id)} onViewProduct={onViewProduct}/>}</div>; })}</section>}
    </div>
  </main>;
}
