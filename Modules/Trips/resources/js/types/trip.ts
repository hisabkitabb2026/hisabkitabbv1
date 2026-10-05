export interface TripStatus {
  id: number
  code: string
  name: string
  colour: string | null
  position: number
  is_default: boolean
  is_closed: boolean
}

export interface TripCustomer {
  id: number | null
  name: string | null
}

export interface TripCard {
  id: number
  trip_no: number
  from_city: string | null
  to_city: string | null
  goods: string | null
  lorry_no: string | null
  status_id: number
  board_position: number
  pickup_date: string | null
  delivered_date: string | null
  cancelled_at: string | null
  customers: TripCustomer[]
  lr_count: number
  revenue: number
  cost: number
  profit: number
  owner_balance: number
}

export interface TripReceipt {
  id: number
  invoice_id: number
  type: 'lr' | 'lorry' | 'invoice'
  amount: number
  invoice_number: string | null
  customer_id: number | null
  customer_name: string | null
  template_name: string | null
  billed_invoice_id: number | null
  billed_at: string | null
}

export interface TripEvent {
  id: number
  type: string
  message: string | null
  user_id: number | null
  created_at: string | null
}

export interface TripExpense {
  id: number
  category: string
  amount: number
  note: string | null
  expense_date: string | null
}

export interface TripMoney {
  revenue: number
  cost: number
  owner_paid: number
  owner_balance: number
  profit: number
  bill_id: number | null
  bill_number: string | null
  bill_status: string | null
  receivable: TripReceivable[]
  payable: TripPayable[]
}

export interface TripReceivablePayment {
  id: number
  number: string | null
  date: string | null
  amount: number
  notes: string | null
  method: string | null
}

export interface TripReceivable {
  invoice_id: number
  invoice_number: string
  customer_name: string | null
  total: number
  due_amount: number
  paid_status: string
  payments: TripReceivablePayment[]
}

export interface TripPayablePayment {
  id: number
  type: 'advance' | 'final'
  number: string | null
  date: string | null
  amount: number
  reference: string | null
  notes: string | null
  method: string | null
}

export interface TripPayable {
  invoice_number: string
  bill_id: number | null
  bill_number: string | null
  bill_reference: string | null
  supplier_name: string | null
  bill_total: number | null
  bill_due: number | null
  bill_status: string | null
  bill_due_date: string | null
  payments: TripPayablePayment[]
}

export interface TripDocument {
  id: number
  name: string
  file_name: string
  mime_type: string
  size: number
  url: string
  is_image: boolean
  category: string | null
  created_at: string | null
}

export interface Trip {
  id: number
  trip_no: number
  from_city: string | null
  to_city: string | null
  goods: string | null
  weight: string | null
  e_way_bill: string | null
  pickup_date: string | null
  delivered_date: string | null
  lorry_no: string | null
  owner_party_id: number | null
  driver_party_id: number | null
  broker_party_id: number | null
  notes: string | null
  cancelled_at: string | null
  status: TripStatus
  receipts: TripReceipt[]
  events: TripEvent[]
  expenses: TripExpense[]
  money: TripMoney
  documents: TripDocument[]
}

export interface UnlinkedReceipt {
  id: number
  invoice_number: string
  customer_id: number | null
  customer_name: string | null
  from_city: string | null
  to_city: string | null
  goods: string | null
  amount: number
  invoice_date: string | null
}

export interface CustomerOption {
  id: number
  name: string
}

export interface PartyProfile {
  id: number
  name: string
  type: string
}
