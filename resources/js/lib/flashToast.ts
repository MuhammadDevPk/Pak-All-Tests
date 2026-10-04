import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import type { FlashToast } from '@/types/ui';

export function initializeFlashToast(): void {
    router.on('finish', () => {
        // @ts-expect-error Inertia global page state
        const flash = router.page?.props?.flash;

        if (!flash) {
            return;
        }

        if (flash.toast) {
            const data = flash.toast as FlashToast;
            toast[data.type](data.message);
        } else if (flash.warning) {
            toast.warning(flash.warning);
        } else if (flash.error) {
            toast.error(flash.error);
        } else if (flash.success) {
            toast.success(flash.success);
        }
    });

    router.on('flash', (event) => {
        const flash = (event as CustomEvent).detail?.flash;
        const data = flash?.toast as FlashToast | undefined;

        if (!data) {
            return;
        }

        toast[data.type](data.message);
    });
}
