import type { ReactNode } from "react";
import type { Product } from "../types/shop";
import { Icon, Stars } from "./ui";

export function ProductCard({ product, wishlisted, onWish, onView, onAdd, footer }: { product: Product; wishlisted: boolean; onWish: () => void; onView: () => void; onAdd: () => void; footer?: ReactNode }) {
  const soldOut = product.in_stock === false;
  return <article className={`product-card ${soldOut ? "sold-out" : ""}`}>
    <div className="product-image" onClick={onView}>
      <img src={product.image} alt={product.name}/>
      {soldOut ? <span className="product-badge sold-out">SOLD OUT</span> : product.badge && <span className="product-badge">{product.badge}</span>}
      <button className={`heart ${wishlisted ? "active" : ""}`} onClick={(e) => { e.stopPropagation(); onWish(); }} aria-label="Add to wishlist"><Icon name="heart"/></button>
      {!soldOut && <button className="quick-add" onClick={(e) => { e.stopPropagation(); onAdd(); }} aria-label={`Quick add ${product.name} to cart`}>QUICK ADD</button>}
    </div>
    <button className="product-info" onClick={onView}>
      <span className="eyebrow">{product.brand}</span><strong>{product.name}</strong>
      <span className="product-meta"><span>฿{product.price.toLocaleString()}</span><span><Stars value={product.rating}/> <i>({product.reviews})</i></span></span>
    </button>
    {footer}
  </article>;
}

export default ProductCard;
