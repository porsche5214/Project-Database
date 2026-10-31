import type { ApiOrder } from "../api/orders";
import { Button, Icon } from "./ui";

// What was ordered, where it ships, and what the member can do next.
export default function OrderDetails({ order, onConfirmReceived, onViewProduct }: { order: ApiOrder; onConfirmReceived?: () => void; onViewProduct: (productId: number) => void }) {
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

// Progress of an order, driven by its real status.
function OrderTimeline({ status }: { status: string }) {
  if (status === "cancelled") {
    return <div className="timeline"><div className="done"><i><Icon name="check"/></i><strong>ORDER CANCELLED</strong><span>STOCK RETURNED</span></div></div>;
  }
  const steps = [["pending", "ORDER PLACED"], ["paid", "PAID"], ["shipped", "SHIPPED"], ["completed", "COMPLETED"]];
  const current = steps.findIndex(([key]) => key === status);
  return <div className="timeline">{steps.map(([key, label], index) => <div className={index <= current ? "done" : ""} key={key}><i>{index <= current ? <Icon name="check"/> : index + 1}</i><strong>{label}</strong><span>{index <= current ? "DONE" : "PENDING"}</span></div>)}</div>;
}
