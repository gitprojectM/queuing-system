export type NotificationAudio = {
    /**
     * Attempts to unlock audio (requires user gesture in most browsers).
     * Returns true if an unlock attempt succeeded.
     */
    unlock: () => Promise<boolean>;
    /**
     * Plays the sound. Returns true if playback started.
     * If this returns false, the browser likely blocked autoplay.
     */
    play: () => Promise<boolean>;
    /**
     * Indicates whether we've successfully unlocked audio at least once.
     */
    isUnlocked: () => boolean;
    /**
     * Last playback error (useful for UI/debug).
     */
    lastError: () => unknown;
    dispose: () => void;
};

/**
 * Creates a reusable, preloaded notification sound player.
 *
 * Many browsers require a user gesture before audio can play.
 * This helper "unlocks" audio on the first pointer/key interaction.
 */
export function createNotificationAudio(src = '/sounds/notify.mp3'): NotificationAudio {
    const audio = new Audio(src);
    audio.preload = 'auto';
    audio.load();

    let unlocked = false;
    let lastErr: unknown = null;

    const doUnlock = async () => {
        try {
            // Attempt a silent play/pause to satisfy gesture requirement.
            audio.muted = true;
            const p = audio.play();
            if (p && typeof (p as Promise<void>).then === 'function') {
                await p;
            }
            audio.pause();
            audio.currentTime = 0;
            unlocked = true;
            lastErr = null;
            return true;
        } catch {
            // Ignore: browser may still block until an explicit gesture.
            return false;
        } finally {
            audio.muted = false;
        }
    };

    window.addEventListener('pointerdown', doUnlock, { once: true });
    window.addEventListener('keydown', doUnlock, { once: true });

    return {
        unlock: doUnlock,
        async play() {
            try {
                // Restart cleanly if already playing.
                audio.pause();
                audio.currentTime = 0;
                const p = audio.play();
                if (p && typeof (p as Promise<void>).then === 'function') {
                    await p;
                }
                unlocked = true;
                lastErr = null;
                return true;
            } catch (e) {
                lastErr = e;
                return false;
            }
        },
        isUnlocked() {
            return unlocked;
        },
        lastError() {
            return lastErr;
        },
        dispose() {
            // With { once: true } listeners, these will usually already be removed.
            window.removeEventListener('pointerdown', doUnlock);
            window.removeEventListener('keydown', doUnlock);
        },
    };
}
