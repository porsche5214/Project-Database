import { FormEvent, useState } from "react";
import { authApi } from "../api/auth";
import { photos } from "../data/products";
import type { Page } from "../types/shop";
import { Button, Field } from "./ui";

// Shared login / sign-up form (LoginPage and RegisterPage pick the mode).
export default function AuthForm({ mode, go, onSuccess, message }: { mode: "login" | "register"; go: (p: Page) => void; onSuccess: () => void; message?: string }) {
  const [errorText, setErrorText] = useState("");
  const [loading, setLoading] = useState(false);

  const submit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setErrorText("");
    const formData = new FormData(event.currentTarget);

    if (mode === "register") {
      const confirmation = event.currentTarget.elements.namedItem("confirmPassword") as HTMLInputElement;
      const matches = formData.get("password") === formData.get("confirmPassword");
      confirmation.setCustomValidity(matches ? "" : "Passwords must match.");
      if (!matches) { confirmation.reportValidity(); return; }

      setLoading(true);
      try {
        await authApi.register({
          username: String(formData.get("username") || ""),
          email: String(formData.get("email") || ""),
          password: String(formData.get("password") || ""),
          first_name: String(formData.get("firstName") || ""),
          last_name: String(formData.get("lastName") || ""),
          phone: String(formData.get("phone") || ""),
        });
        onSuccess();
      } catch (err: any) {
        setErrorText(err.getFirstError ? err.getFirstError() : err.message || "Registration failed");
      } finally {
        setLoading(false);
      }
      return;
    }

    if (mode === "login") {
      setLoading(true);
      try {
        await authApi.login({
          login: String(formData.get("username") || ""),
          password: String(formData.get("password") || ""),
        });
        onSuccess();
      } catch (err: any) {
        setErrorText(err.getFirstError ? err.getFirstError() : err.message || "Invalid credentials");
      } finally {
        setLoading(false);
      }
    }
  };

  return <main className="auth-page"><div className="auth-image"><img src={photos[7]} alt="NaeMak campaign"/><span>BE BOLD.<br/>BE NAEMAK.</span></div><form className={`auth-form auth-form-${mode}`} onSubmit={submit}><span className="eyebrow">NAEMAK MEMBERS</span><h1>{mode === "login" ? "WELCOME BACK" : "CREATE ACCOUNT"}</h1><p>{errorText || message || (mode === "login" ? "Log in to access your wardrobe, orders and wishlist." : "Join NaeMak for a more personal shopping experience.")}</p>{mode === "register" && <div className="two-inputs"><Field label="FIRST NAME" name="firstName"/><Field label="LAST NAME" name="lastName"/></div>}<Field label={mode === "login" ? "USERNAME OR EMAIL" : "USERNAME"} name="username" type="text"/>{mode === "register" && <Field label="EMAIL" name="email" type="email"/>}<Field label="PASSWORD" name="password" type="password"/>{mode === "register" && <><Field label="CONFIRM PASSWORD" name="confirmPassword" type="password"/><Field label="PHONE" name="phone"/></>}<div className="auth-extra">{mode === "login" && <label><input type="checkbox"/> Remember me</label>}<button type="button">{mode === "login" ? "Forgot Password?" : "Privacy policy"}</button></div><Button type="submit" disabled={loading}>{mode === "login" ? "LOG IN" : "CREATE ACCOUNT"}</Button><div className="auth-switch">{mode === "login" ? "NEW TO NAEMAK?" : "ALREADY HAVE AN ACCOUNT?"}<button type="button" onClick={() => go(mode === "login" ? "register" : "login")}>{mode === "login" ? "CREATE AN ACCOUNT" : "LOG IN"}</button></div></form></main>;
}
