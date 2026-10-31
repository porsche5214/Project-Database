import { useState } from "react";
import ReviewsSection from "../components/ReviewsSection";
import { Button, Icon, Stars } from "../components/ui";
import type { Page, Product, ProductReview } from "../types/shop";

type StockVariant = { color: string; size: string; stock: number };

export default function ProductDetailPage({ product, onAdd, onWish, go, loggedIn, reviews, onSubmitReview }: { product: Product & { description?: string; variants?: StockVariant[] }; onAdd: (color: string, size: string, quantity: number) => void; onWish: () => void; go: (p: Page) => void; loggedIn: boolean; reviews: ProductReview[]; onSubmitReview: (review: Pick<ProductReview, "title" | "text" | "rating">) => Promise<boolean> }) {
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
        {product.description && <p className="description">{product.description}</p>}
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
