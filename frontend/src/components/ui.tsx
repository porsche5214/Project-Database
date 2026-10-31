import { useState, type ReactNode } from "react";

export function Icon({ name, size = 20 }: { name: string; size?: number }) {
  const paths: Record<string, ReactNode> = {
    search: <><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></>,
    heart: <path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8l1.1 1.1L12 21l7.8-7.5 1.1-1.1a5.5 5.5 0 0 0-.1-7.8Z"/>,
    bag: <><path d="M5 8h14l-1 13H6L5 8Z"/><path d="M9 9V6a3 3 0 0 1 6 0v3"/></>,
    user: <><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></>,
    menu: <><path d="M3 7h18M3 12h18M3 17h18"/></>,
    close: <path d="m6 6 12 12M18 6 6 18"/>,
    arrow: <path d="M5 12h14m-5-5 5 5-5 5"/>,
    chevron: <path d="m9 18 6-6-6-6"/>,
    trash: <><path d="M4 7h16M9 7V4h6v3m3 0-1 14H7L6 7"/><path d="M10 11v6m4-6v6"/></>,
    check: <path d="m5 12 4 4L19 6"/>,
    filter: <path d="M4 5h16M7 12h10m-7 7h4"/>,
  };
  return <svg width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">{paths[name]}</svg>;
}

// Filled stars follow the score (2.0 = 2 stars, 4.5 = 4½): a full-width
// row of filled stars is clipped to value/5 on top of a row of empty ones.
export function Stars({ value, dark = false }: { value: number; dark?: boolean }) {
  const filled = Math.max(0, Math.min(5, value)) / 5 * 100;
  return <span className={`stars ${dark ? "dark" : ""}`} aria-label={`${value.toFixed(1)} out of 5 stars`}><span className="stars-track" aria-hidden="true">★★★★★<span className="stars-fill" style={{ width: `${filled}%` }}>★★★★★</span></span><small>{value.toFixed(1)}</small></span>;
}

export function Button({ children, onClick, variant = "dark", type = "button", disabled = false }: { children: ReactNode; onClick?: () => void; variant?: "dark" | "light" | "outline" | "text"; type?: "button" | "submit"; disabled?: boolean }) {
  return <button type={type} className={`btn btn-${variant}`} onClick={onClick} disabled={disabled}>{children}</button>;
}

export function SectionTitle({ kicker, title, action, onAction }: { kicker: string; title: string; action?: string; onAction?: () => void }) {
  return <div className="section-title"><div><span className="eyebrow">{kicker}</span><h2>{title}</h2></div>{action && <button onClick={onAction}>{action} <Icon name="arrow"/></button>}</div>;
}

export function Empty({ title, text, action, onAction }: { title: string; text: string; action: string; onAction: () => void }) {
  return <section className="empty"><div className="empty-icon"><Icon name="bag" size={32}/></div><h1>{title}</h1><p>{text}</p><Button onClick={onAction}>{action}</Button></section>;
}

export function CheckoutField({ label, value, onChange, type = "text", readOnly = false }: { label: string; value: string; onChange: (value: string) => void; type?: string; readOnly?: boolean }) {
  return <label className="field"><span>{label}</span><input type={type} required value={value} readOnly={readOnly} onChange={(e) => onChange(e.target.value)}/></label>;
}

export function Field({ label, type = "text", name }: { label: string; type?: string; name?: string }) {
  const [passwordVisible, setPasswordVisible] = useState(false);
  const isPassword = type === "password";
  return <label className={`field${isPassword ? " password-field" : ""}${name ? ` ${name}-field` : ""}`}><span>{label}</span>{isPassword ? <span className="field-control"><input name={name} type={passwordVisible ? "text" : "password"} required minLength={8} placeholder="••••••••"/><button type="button" onClick={() => setPasswordVisible((visible) => !visible)} aria-label={passwordVisible ? `Hide ${label.toLowerCase()}` : `Show ${label.toLowerCase()}`}>{passwordVisible ? "HIDE" : "SHOW"}</button></span> : <input name={name} type={type} required/>}</label>;
}
