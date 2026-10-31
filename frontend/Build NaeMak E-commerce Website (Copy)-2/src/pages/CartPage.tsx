import Summary from "../components/Summary";
import { Empty } from "../components/ui";
import { cartItemKey, type CartItem, type Page } from "../types/shop";

export default function CartPage({ items, updateQty, remove, go }: { items: CartItem[]; updateQty: (key: string, d: number) => void; remove: (key: string) => void; go: (p: Page) => void }) {
  const subtotal = items.reduce((s, x) => s + x.product.price * x.qty, 0);
  if (!items.length) return <main><Empty title="Your cart is empty." text="Your next favorite piece is waiting." action="CONTINUE SHOPPING" onAction={() => go("products")}/></main>;
  return <main className="cart-page"><div className="page-heading"><span className="eyebrow">YOUR SELECTION</span><h1>SHOPPING CART</h1></div><div className="cart-layout"><div className="cart-items"><div className="cart-labels"><span>PRODUCT</span><span>QUANTITY</span><span>SUBTOTAL</span></div>{items.map((x) => { const key = cartItemKey(x); return <article className="cart-item" key={key}><img src={x.product.image} alt={x.product.name}/><div className="cart-product"><span className="eyebrow">{x.product.brand}</span><strong>{x.product.name}</strong><span>{x.color} / {x.size}</span><button onClick={() => remove(key)}>REMOVE</button></div><div className="quantity"><button onClick={() => updateQty(key, -1)} aria-label={`Decrease ${x.product.name} quantity`}>−</button><span>{x.qty}</span><button onClick={() => updateQty(key, 1)} aria-label={`Increase ${x.product.name} quantity`}>+</button></div><b>฿{(x.product.price * x.qty).toLocaleString()}</b></article>; })}</div><Summary subtotal={subtotal} action="PROCEED TO CHECKOUT" onAction={() => go("checkout")}/></div></main>;
}
