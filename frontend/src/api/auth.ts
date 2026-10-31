import { api } from "./client";
import type { MemberInfo } from "../types/shop";

export type ApiUser = {
  member_id: number;
  username: string;
  email: string;
  first_name: string;
  last_name: string;
  phone: string;
  role: string;
  default_address?: {
    address_id: number;
    recipient_name: string;
    phone: string;
    address_line: string;
    subdistrict: string;
    district: string;
    province: string;
    postal_code: string;
    full_address: string;
  } | null;
  addresses?: Array<{
    address_id: number;
    recipient_name: string;
    phone: string;
    address_line: string;
    district: string;
    province: string;
    postal_code: string;
    is_default: boolean;
    full_address: string;
  }>;
};

export function mapUserToMemberInfo(user: ApiUser): MemberInfo {
  const addr = user.default_address || (user.addresses && user.addresses[0]);
  return {
    email: user.email || "",
    phone: user.phone || "",
    firstName: user.first_name || "",
    lastName: user.last_name || "",
    address: addr?.address_line || "",
    city: addr?.district || "",
    province: addr?.province || "",
    postalCode: addr?.postal_code || "",
    country: "Thailand",
  };
}

export const authApi = {
  me: () => api.get<ApiUser | null>("/me"),
  login: (credentials: { login?: string; username?: string; password: string; remember?: boolean }) =>
    api.post<{ message: string; user: ApiUser }>("/login", credentials),
  register: (data: {
    username: string;
    email: string;
    password: string;
    first_name?: string;
    last_name?: string;
    phone?: string;
    address?: string;
  }) => api.post<{ message: string; user: ApiUser }>("/register", data),
  logout: () => api.post<{ message: string }>("/logout"),
  updateProfile: (data: {
    first_name?: string;
    last_name?: string;
    email: string;
    phone?: string;
    address?: string;
    city?: string;
    province?: string;
    postal_code?: string;
    country?: string;
  }) => api.put<{ message: string; user: ApiUser }>("/profile", data),
};
