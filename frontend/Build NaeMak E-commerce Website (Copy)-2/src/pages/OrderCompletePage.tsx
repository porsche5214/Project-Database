import type { ApiOrder } from "../api/orders";
import { Button, Icon } from "../components/ui";
import type { Page } from "../types/shop";

// Order complete page shown right after checkout.
export default function OrderCompletePage({ order, go }: { order: ApiOrder; go: (p: Page) => void }) {
  const recipientName = (order.ship_name || "").toUpperCase();
  const itemCount = order.items.reduce((sum, item) => sum + item.quantity, 0);

  return <main className="order-complete">
    <section className="confirmation"><i><Icon name="check" size={36}/></i><span className="eyebrow">THANK YOU{recipientName ? `, ${recipientName}` : ""}</span><h1>ORDER<br/>COMPLETE.</h1><p>Your order <b>#{order.order_number}</b> has been placed and completed.<br/>You can review the items you bought right away.</p><div><span>ORDER DATE <b>{order.formatted_date}</b></span><span>ITEMS <b>{itemCount}</b></span><span>TOTAL <b>฿{Number(order.total_amount).toLocaleString()}</b></span><span>STATUS <b>{order.status.toUpperCase()}</b></span></div><nav className="confirmation-actions"><Button onClick={() => go("orders")}>VIEW MY ORDERS</Button><Button variant="outline" onClick={() => go("products")}>CONTINUE SHOPPING</Button></nav></section>
  </main>;
}
