export type ApiEnvelope<T> = {
  success: boolean;
  message?: string;
  data?: T;
  error?: string | { code: string; message: string; details?: unknown } | null;
};

export type LoginRequest = {
  email: string;
  password: string;
};

export type Permission =
  | 'dealer:spare_parts:read'
  | 'dealer:orders:read'
  | 'dealer:orders:write'
  | 'staff:items:read'
  | 'staff:items:write'
  | 'staff:orders:read'
  | 'staff:orders:write'
  | 'staff:reports:read';

export type Role = 'dealer' | 'staff';

export type AuthSession = {
  token: string;
  role: Role;
  permissions: Permission[];
  userPublicId: string;
};

export type OrderStatus = 'pending' | 'confirmed' | 'processing' | 'shipping' | 'shipped' | 'cancelled';

export type OrderSummary = {
  orderPublicId: string;
  dealerName: string;
  totalAmount: number;
  status: OrderStatus;
  createdAt: string;
};

export type OrderDetail = OrderSummary & {
  shippingAddress: string;
  contactName: string;
  contactPhone: string;
  items: Array<{
    sku: string;
    name: string;
    quantity: number;
    unitPrice: number;
  }>;
};

export type SparePartSummary = {
  partPublicId: string;
  sku: string;
  name: string;
  category: string;
  stockQty: number;
  unitPrice: number;
  updatedAt: string;
};

export type StaffItem = SparePartSummary & {
  reorderLevel: number;
  isActive: boolean;
};

export type UserProfile = {
  userPublicId: string;
  displayName: string;
  email: string;
  phone: string;
  role: Role;
  permissions: Permission[];
  lastLoginAt?: string;
};
