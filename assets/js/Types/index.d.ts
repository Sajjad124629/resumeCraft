import type { LucideIcon } from 'lucide-vue-next';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
    isActive?: boolean;
}

export type AppPageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    ziggy?: any;
    sidebarOpen: boolean;
};

export interface UserDetail {
    id: number;
    user_id: number;
    fullname: string | null;
    country_id: number | null;
    state_id: number | null;
    city_id: number | null;
    title: string | null;
    image: File | null;
    address: string;
    created_at: string;
    updated_at: string;
}

export interface User {
    id: number;
    username: string;
    email?: string;
    phone_number?: string;
    avatar?: string;
    user_detail: UserDetail;
    designation: Designation;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}
export interface Settings {
  logo?: string;
  title?: string;
  developed_by?: string;
}
export type BreadcrumbItemType = BreadcrumbItem;
