import { describe, expect, it } from 'vitest'
import { canStartPoliceVetting, canStartNisVetting } from '../vettingWorkflow'

describe('vettingWorkflow', () => {
  it('allows police vetting only for assigned files in police-stage workflows', () => {
    expect(
      canStartPoliceVetting(
        {
          status: { code: 'police_vetting' },
          assigned_police_officer: { id: 10 },
          police_vetting_completed_at: null
        } as any,
        10
      )
    ).toBe(true)

    expect(
      canStartPoliceVetting(
        {
          status: { code: 'pending_approval' },
          assigned_police_officer: { id: 10 },
          police_vetting_completed_at: null
        } as any,
        10
      )
    ).toBe(false)
  })

  it('allows NIS vetting only when the file has entered NIS stage', () => {
    expect(
      canStartNisVetting(
        {
          status: { code: 'nis_vetting' },
          assigned_nis_officer: { id: 20 },
          nis_vetting_completed_at: null
        } as any,
        20
      )
    ).toBe(true)

    expect(
      canStartNisVetting(
        {
          status: { code: 'police_vetting' },
          assigned_nis_officer: { id: 20 },
          nis_vetting_completed_at: null
        } as any,
        20
      )
    ).toBe(false)
  })
})
