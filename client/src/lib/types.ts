/**
 * Shapes returned by the Laravel API. These mirror the `App\Http\Resources`
 * classes on the server.
 */

export type Role = 'foreman' | 'field' | 'estimator';

export interface Capabilities {
  can_see_routes: boolean;
  can_see_chemicals: boolean;
  can_see_estimates: boolean;
}

export interface Employee {
  id: number;
  name: string;
  first_name: string | null;
  last_name: string | null;
  email: string | null;
  phone: string | null;
  address: string | null;
  city: string | null;
  state: string | null;
  zip: string | null;
  role: Role;
  role_label: string;
  division: string | null;
  avatar_url: string | null;
  capabilities: Capabilities;
}

export interface Customer {
  id: number;
  name: string;
  company_name: string | null;
  contact_name: string | null;
  email: string | null;
  phone: string | null;
  address: string | null;
  city: string | null;
  state: string | null;
  zip: string | null;
}

export interface Property {
  id: number;
  address: string | null;
  city: string | null;
  state: string | null;
  zip: string | null;
  full_address: string;
  latitude: number | null;
  longitude: number | null;
  square_footage: number | null;
}

export interface JobMessage {
  id: number;
  body: string;
  sender: string | null;
  created_at: string | null;
  created_human: string | null;
}

export interface JobMedia {
  id: number;
  type: string;
  url: string;
  original_name: string;
  size: number;
  created_at: string | null;
}

export type JobStatus = 'scheduled' | 'in_progress' | 'completed' | 'cancelled' | 'pending';

export interface Job {
  id: number;
  title: string;
  description: string | null;
  status: JobStatus | string;
  priority: string;
  scheduled_date: string | null;
  completed_date: string | null;
  started_at: string | null;
  finished_at: string | null;
  notes: string | null;
  route_order: number | null;
  crew: { id: number; name: string | null } | null;
  customer: Customer | null;
  property: Property | null;
  messages: JobMessage[];
  media: JobMedia[];
}

export interface SchedulePayload {
  date: string;
  is_today: boolean;
  job_count: number;
  jobs: Job[];
}

export interface TimeLog {
  id: number;
  clock_in: string | null;
  clock_out: string | null;
  break_minutes: number;
  status: string;
  is_active: boolean;
  duration_minutes: number;
}

export interface TimeLogsPayload {
  active_shift: TimeLog | null;
  minutes_today: number;
  today: TimeLog[];
  history: TimeLog[];
}

export type QuoteStatus = 'draft' | 'sent' | 'accepted' | 'declined' | 'expired';

export interface QuoteLineItem {
  id: number;
  description: string;
  quantity: number;
  unit_price: number;
  total: number;
  sort_order: number;
}

export interface Quote {
  id: number;
  estimate_number: string;
  status: QuoteStatus | string;
  subtotal: number;
  tax: number;
  total: number;
  square_footage: number | null;
  valid_until: string | null;
  notes: string | null;
  sent_at: string | null;
  accepted_at: string | null;
  created_at: string | null;
  created_human: string | null;
  public_url: string;
  customer: Customer | null;
  property: Property | null;
  line_items: QuoteLineItem[];
}

export interface ServiceOption {
  id: number;
  name: string;
  default_price: number;
}

/** Draft line item used by the quote editor before it is saved. */
export interface QuoteLineItemDraft {
  description: string;
  quantity: string;
  unit_price: string;
}

export interface ChatAttachment {
  type: 'photo' | 'video';
  url: string;
  name: string | null;
}

export interface ChatMessage {
  id: number;
  sender: 'foreman' | 'office';
  sender_name: string;
  body: string | null;
  attachment: ChatAttachment | null;
  read_at: string | null;
  created_at: string | null;
  created_human: string | null;
}

export interface QuotePayload {
  customer_id: number;
  property_id?: number | null;
  status?: QuoteStatus;
  square_footage?: number | null;
  valid_until?: string | null;
  notes?: string | null;
  line_items: { description: string; quantity: number; unit_price: number }[];
}
