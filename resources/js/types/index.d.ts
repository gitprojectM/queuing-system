import type { LucideIcon } from 'lucide-vue-next';
import type { Config } from 'ziggy-js';

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
    adminOnly?: boolean;
}

export type AppPageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    ziggy: Config & { location: string };
    sidebarOpen: boolean;
};

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    role?: string;
}

export interface Service {
    id: number;
    name: string;
    description?: string | null;
    created_at: string;
    updated_at: string;
    windows?: Window[];
    users?: User[];
    queues?: Queue[];
}

export interface Window {
    id: number;
    name: string;
    description?: string | null;
    current_client_id?: number | null;
    created_at: string;
    updated_at: string;
    services?: Service[];
    users?: User[];
    current_client?: Queue | null;
    waiting_queues?: Queue[];
}

export interface QueueStep {
    id: number;
    queue_id: number;
    service_id: number;
    window_id?: number | null;
    step_order: number;
    status: 'pending' | 'waiting' | 'assigned' | 'completed';
    started_at?: string | null;
    completed_at?: string | null;
    created_at: string;
    updated_at: string;
    service?: Service;
    window?: Window;
}

export interface Queue {
    id: number;
    name: string;
    user_id?: number | null;
    service_id: number;
    window_id?: number | null;
    queue_number: number;
    status: 'waiting' | 'assigned' | 'completed' | 'cancelled';
    priority: number;
    queue_date: string;
    created_at: string;
    updated_at: string;
    service?: Service;
    window?: Window;
    user?: User;
    steps?: QueueStep[];
}

export type BreadcrumbItemType = BreadcrumbItem;
