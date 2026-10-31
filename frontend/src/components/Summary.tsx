import { Button } from "./ui";

// Order summary box used by the cart and checkout pages.
export default function Summary({ subtotal, action, onAction, actionType = "button" }: { subtotal: number; action: string; onAction?: () => void; actionType?: "button" | "submit" }) {
  const shipping = subtotal >= 2500 ? 0 : 150;
  return <aside className="summary"><h2>ORDER SUMMARY</h2><div><span>Subtotal</span><b>฿{subtotal.toLocaleString()}</b></div><div><span>Shipping</span><b>{shipping ? `฿${shipping}` : "COMPLIMENTARY"}</b></div><div className="summary-total"><span>TOTAL</span><strong>฿{(subtotal + shipping).toLocaleString()}</strong></div><Button type={actionType} onClick={onAction}>{action}</Button><p>SECURE CHECKOUT · EASY RETURNS</p></aside>;
}
