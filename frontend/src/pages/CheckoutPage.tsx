import { FormEvent, useState } from "react";
import Summary from "../components/Summary";
import { CheckoutField } from "../components/ui";
import { cartItemKey, type MemberInfo, type Page, type Product } from "../types/shop";

export default function CheckoutPage({ items, go, loggedIn, memberInfo, onSaveInfo, onPlaceOrder }: { items: { product: Product; qty: number; color: string; size: string }[]; go: (p: Page) => void; loggedIn: boolean; memberInfo: MemberInfo; onSaveInfo: (info: MemberInfo) => void; onPlaceOrder?: (info: MemberInfo, payment: string, saveAddress: boolean) => Promise<boolean | void> }) {
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
