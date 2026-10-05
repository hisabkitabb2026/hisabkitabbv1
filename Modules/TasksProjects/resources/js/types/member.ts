/** A user of the active company, as the module's members endpoint renders it. */
export interface CompanyMember {
  id: number
  name: string
  email: string
  avatar: string | null
}
