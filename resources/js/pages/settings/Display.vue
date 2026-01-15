<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { type BreadcrumbItem } from '@/types';

const props = defineProps<{ videoUrl: string }>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Display settings', href: '/settings/display' },
];

const form = useForm({
    video_url: props.videoUrl ?? '',
});

const isPublicPath = computed(() => (form.video_url || '').trim().startsWith('/'));
const isAbsoluteUrl = computed(() => {
    try {
        const u = new URL((form.video_url || '').trim());
        return u.protocol === 'http:' || u.protocol === 'https:';
    } catch {
        return false;
    }
});

function submit() {
    form.put(route('settings.display.update'), {
        preserveScroll: true,
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Display settings" />

        <SettingsLayout>
            <div class="space-y-6">
                <HeadingSmall
                    title="Now Serving video"
                    description="Set the video shown on the public Now Serving display. Use a direct video file URL (mp4/webm) or a public path like /videos/promo.mp4. Leave empty to disable."
                />

                <form class="space-y-6" @submit.prevent="submit">
                    <div class="grid gap-2">
                        <Label for="video_url">Video URL</Label>
                        <Input
                            id="video_url"
                            name="video_url"
                            type="text"
                            class="mt-1 block w-full"
                            v-model="form.video_url"
                            placeholder="https://example.com/promo.mp4 or /videos/promo.mp4"
                        />
                        <InputError class="mt-2" :message="form.errors.video_url" />
                        <p class="text-xs text-muted-foreground">
                            Status:
                            <span v-if="!form.video_url">disabled</span>
                            <span v-else-if="isAbsoluteUrl || isPublicPath">looks valid</span>
                            <span v-else>invalid (must start with https:// or /)</span>
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <Button :disabled="form.processing">Save</Button>
                        <p v-if="form.recentlySuccessful" class="text-sm text-neutral-600">Saved.</p>
                    </div>
                </form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
