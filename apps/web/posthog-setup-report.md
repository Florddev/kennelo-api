<wizard-report>
# PostHog post-wizard report

The wizard has completed a full PostHog integration for the **Kennelo** web app (Next.js 16 App Router). The integration uses `instrumentation-client.ts` for client-side initialization (the recommended pattern for Next.js 15.3+), a reverse proxy via Next.js rewrites to avoid ad blockers, a shared server-side PostHog client via `posthog-node`, and targeted `posthog.capture()` / `posthog.identify()` calls in nine key business locations across auth, booking, pet management, search, and subscription flows.

| Event                 | Description                                                                        | File                                                                        |
| --------------------- | ---------------------------------------------------------------------------------- | --------------------------------------------------------------------------- |
| `user_signed_up`      | Fired when a user completes email registration.                                    | `features/auth/components/forms/register-form.tsx`                          |
| `user_logged_in`      | Fired when a user successfully logs in via email/password.                         | `features/auth/components/forms/login-form.tsx`                             |
| `user_logged_in`      | Fired when a user successfully logs in via Google OAuth.                           | `features/auth/components/google-sign-in-button.tsx`                        |
| `user_logged_out`     | Fired when a user logs out of the application.                                     | `features/auth/hooks/use-auth.tsx`                                          |
| `booking_created`     | Fired when a user successfully submits a booking and payment completes.            | `features/bookings/components/booking-checkout-form.tsx`                    |
| `host_viewed`         | Fired when a user views a host listing detail page (top of booking funnel).        | `app/[locale]/(main)/host/[id]/host-detail-page.tsx`                        |
| `search_performed`    | Fired when a user performs a host search with location, dates, and/or pet filters. | `app/[locale]/(main)/explore/results/results-page.tsx`                      |
| `pet_created`         | Fired when a user completes the new pet creation stepper.                          | `features/pets/components/forms/create-pet-stepper.tsx`                     |
| `subscription_viewed` | Fired when a host views the subscription management page.                          | `app/[locale]/(hosting)/hosting/subscription/hosting-subscription-page.tsx` |

User identification (`posthog.identify()`) is called on every page load where the user is authenticated (via `use-auth.tsx`'s `loadUser`), linking the PostHog anonymous ID to the application user ID with `email`, `firstName`, `lastName`, and `locale` as person properties. `posthog.reset()` is called on logout to unlink the session.

## Next steps

We've built some insights and a dashboard for you to keep an eye on user behavior, based on the events we just instrumented:

- **Dashboard**: [Analytics basics (wizard)](https://eu.posthog.com/project/223411/dashboard/820915)
- [User sign-ups & logins (wizard)](https://eu.posthog.com/project/223411/insights/mRVlsAhJ)
- [Booking conversion funnel (wizard)](https://eu.posthog.com/project/223411/insights/UTFs8xJS)
- [Bookings created over time (wizard)](https://eu.posthog.com/project/223411/insights/xORX9O3p)
- [Pet creation & search activity (wizard)](https://eu.posthog.com/project/223411/insights/6SbvmOv7)

## Verify before merging

- [ ] Run a full production build (the wizard only verified the files it touched) and fix any lint or type errors introduced by the generated code.
- [ ] Run the test suite — call sites that were rewritten or instrumented may need updated mocks or fixtures.
- [ ] Add `NEXT_PUBLIC_POSTHOG_PROJECT_TOKEN` and `NEXT_PUBLIC_POSTHOG_HOST` to `.env.example` and any monorepo/bootstrap scripts so collaborators know what to set.
- [ ] Wire source-map upload (`posthog-cli sourcemap` or your bundler's upload step) into CI so production stack traces de-minify.
- [ ] Confirm the returning-visitor path also calls `identify` — the `loadUser` function in `use-auth.tsx` identifies on every authenticated page load, so returning sessions should be covered; verify this in PostHog's person profiles after a returning login.

### Agent skill

We've left an agent skill folder in your project. You can use this context for further agent development when using Claude Code. This will help ensure the model provides the most up-to-date approaches for integrating PostHog.

</wizard-report>
