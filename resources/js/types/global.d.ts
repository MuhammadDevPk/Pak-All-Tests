import type { Auth } from '@/types/auth';
import type { Team } from '@/types/teams';

// Extend ImportMeta interface for Vite...
declare module 'vite/client' {
    interface ImportMetaEnv {
        readonly VITE_APP_NAME: string;
        [key: string]: string | boolean | undefined;
    }

    interface ImportMeta {
        readonly env: ImportMetaEnv;
        readonly glob: <T>(pattern: string) => Record<string, () => Promise<T>>;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            currentTeam: Team | null;
            teams: Team[];
            platform?: {
                name?: string;
                portal_name?: string;
                tagline?: string;
                subtitle?: string;
                description?: string;
                support_email?: string;
                support_phone?: string;
                whatsapp_support_number?: string;
                whatsapp_support_url?: string;
                trials?: {
                    free_trial_days?: number;
                    guest_pass_hours?: number;
                    guest_domain_pass_days?: number;
                    guest_email_domain?: string;
                };
                referrals?: {
                    days_per_referral?: number;
                    milestone_target?: number;
                    milestone_days?: number;
                    share_message_template?: string;
                };
                payment_accounts?: Record<string, any>;
            };
            [key: string]: unknown;
        };
    }
}

declare module 'vue' {
    interface ComponentCustomProperties {
        $inertia: typeof Router;
        $page: Page;
        $headManager: ReturnType<typeof createHeadManager>;
    }
}
