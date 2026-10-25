import { api } from "./client";

export type ApiOrderItem = {
  variant_id: number;
  product_id: number;
  name: string;
  brand: string;
  image: string;
  color: string;
  size: string;
  quantity: number;
  unit_price: number;
  line_total: number;
};

export type ApiOrder = {
  order_id: number;
  order_number: string;
  order_date: string;
  formatted_date: string;
  status: string;
  total_amount: number;
  contact_email: string;
  ship_name: string;
  ship_phone: string;
  ship_address: string;
  items: ApiOrderItem[];
};

export type CheckoutPayload = {
  address_id?: number;
  email?: string;
  phone?: string;
  first_name?: string;
  last_name?: string;
  firstName?: string;
  lastName?: string;
  address?: string;
  city?: string;
  province?: string;
  postal_code?: string;
  postalCode?: string;
  country?: string;
  save_address?: boolean;
  saveAddress?: boolean;
  payment_method?: string;
};

export const ordersApi = {
  list: async (): Promise<ApiOrder[]> => {
    const res = await api.get<{ orders: ApiOrder[] }>('/orders');
    return res.orders || (res as unknown as ApiOrder[]);
  },
  get: async (id: number): Promise<ApiOrder> => {
    const res = await api.get<{ order: ApiOrder }>(`/orders/${id}`);
    return res.order || (res as unknown as ApiOrder);
  },
  checkout: async (payload: CheckoutPayload): Promise<{ message: string; order: ApiOrder }> => {
    return api.post<{ message: string; order: ApiOrder }>('/checkout', payload);
  },
  confirmReceived: async (id: number): Promise<ApiOrder> => {
    const res = await api.post<{ message: string; order: ApiOrder }>(`/orders/${id}/received`, {});
    return res.order;
  },
};
