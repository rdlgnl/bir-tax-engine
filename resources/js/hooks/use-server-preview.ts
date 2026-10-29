import { useHttp } from '@inertiajs/react';
import type { FormDataType, UrlMethodPair } from '@inertiajs/core';
import { useEffect } from 'react';

/**
 * Sends a half-filled form to a JSON endpoint a moment after it stops
 * changing, and returns the answer. The server stays the only place that
 * computes anything.
 */
export function useServerPreview<
    TPayload extends FormDataType<TPayload>,
    TResponse,
>(route: () => UrlMethodPair, payload: TPayload, ready: boolean) {
    const http = useHttp<TPayload, TResponse>(payload);
    const key = JSON.stringify(payload);

    useEffect(() => {
        if (!ready) {
            return;
        }

        const timer = setTimeout(() => {
            http.cancel();
            http.transform(() => payload);
            http.submit(route()).catch(() => {
                // A half-typed form can fail validation; the preview just waits.
            });
        }, 300);

        return () => clearTimeout(timer);
        // The payload's contents are what matter; `http` changes every render.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [key, ready]);

    return {
        response: ready ? http.response : null,
        loading: http.processing,
    };
}
