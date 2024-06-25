import { usePage } from "@inertiajs/vue3";

export function httpGet(url) {
    return fetch(url, {
        headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
        },
    }).then((response) => response.json());
}

export function httpPost(url, data) {
    const page = usePage();
    return fetch(url, {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
            "X-CSRF-TOKEN": page.props.csrf_token,
        },
        body: JSON.stringify(data),
    }).then((response) => response.json());
}
