import type { MemberInfo } from "../types/shop";
import { Button } from "./ui";

const benefits = [
  { tier: "Silver", items: ["Earn 1 point per purchase amount", "Access to member-only promotions"] },
  { tier: "Gold", items: ["Increased point earning", "Early access to selected collections", "Exclusive discounts"] },
  { tier: "Platinum", items: ["Highest point benefits", "Exclusive member offers", "Priority access to new collections"] },
];

export function MemberTierCard({ memberInfo }: { memberInfo: MemberInfo }) {
  const points = 1250;
  const nextTierAt = 2000;
  const progress = Math.round((points / nextTierAt) * 100);

  return <section className="member-tier-card">
    <div className="member-tier-heading">
      <div><span className="eyebrow">NAEMAK MEMBER</span><h2>SILVER</h2></div>
      <span className="tier-mark">S</span>
    </div>
    <div className="member-identity"><strong>{memberInfo.firstName} {memberInfo.lastName}</strong><span>{memberInfo.email}</span>{memberInfo.phone && <span>{memberInfo.phone}</span>}</div>
    <dl className="member-stats">
      <div><dt>CURRENT POINTS</dt><dd>{points.toLocaleString()} PTS</dd></div>
      <div><dt>NEXT TIER</dt><dd>GOLD</dd></div>
      <div><dt>POINTS NEEDED</dt><dd>{(nextTierAt - points).toLocaleString()} PTS</dd></div>
    </dl>
    <div className="member-progress-copy"><span>SILVER</span><span>GOLD</span></div>
    <div className="member-progress" role="progressbar" aria-label="Progress to Gold membership" aria-valuemin={0} aria-valuemax={nextTierAt} aria-valuenow={points}><span style={{ width: `${progress}%` }}/></div>
    <p>{progress}% of the way to Gold. Points update after each completed order.</p>
  </section>;
}

export function MemberBenefits() {
  return <section className="member-benefits">
    <div className="section-title"><div><span className="eyebrow">YOUR LOYALTY</span><h2>MEMBER BENEFITS</h2></div></div>
    <div className="benefit-grid">{benefits.map(({ tier, items }) => <article className={tier === "Silver" ? "current" : ""} key={tier}><span>{tier === "Silver" ? "CURRENT TIER" : "MEMBER TIER"}</span><h3>{tier}</h3><ul>{items.map((item) => <li key={item}>{item}</li>)}</ul></article>)}</div>
  </section>;
}

export default function MemberProgram({ memberInfo, onOrders, onWishlist }: { memberInfo: MemberInfo; onOrders: () => void; onWishlist: () => void }) {
  return <div className="member-program">
    <MemberTierCard memberInfo={memberInfo}/>
    <div className="member-shortcuts"><Button variant="outline" onClick={onOrders}>VIEW ORDER HISTORY</Button><Button variant="outline" onClick={onWishlist}>VIEW WISHLIST</Button></div>
    <MemberBenefits/>
  </div>;
}
