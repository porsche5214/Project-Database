import { useEffect, useState } from "react";
import { useSearchParams } from "react-router-dom";
import { ordersApi, type ApiOrder } from "../api/orders";
import OrderDetails from "../components/OrderDetails";
import { Button, CheckoutField, Empty, Icon } from "../components/ui";
import type { MemberInfo, Page } from "../types/shop";

// My account: profile (read-only until EDIT PROFILE) and order history.
export default function AccountPage({ memberInfo, go, onSaveInfo, onLogout, onViewProduct }: { memberInfo: MemberInfo; go: (page: Page) => void; onSaveInfo: (info: MemberInfo) => void; onLogout: () => void; onViewProduct: (productId: number) => void }) {
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
  const update = (key: keyof MemberInfo, value: string) => setInfo((current) => ({...current, [key]: value}));
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
