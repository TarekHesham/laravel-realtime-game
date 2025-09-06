import "./bootstrap";
import "../css/app.css";

if (window.axios) {
    window.axios.defaults.headers.common["Access-Control-Allow-Origin"] = "*";
    window.axios.defaults.headers.common["Access-Control-Allow-Methods"] =
        "GET, POST, PUT, DELETE, OPTIONS";
    window.axios.defaults.headers.common["Access-Control-Allow-Headers"] =
        "Content-Type, Authorization, X-Requested-With, X-CSRF-TOKEN";
    window.axios.defaults.timeout = 10000;

    window.axios.interceptors.response.use(
        (response) => response,
        (error) => {
            console.error("Axios Error:", error);
            if (error.code === "ECONNABORTED") {
                console.error("Request timeout");
            } else if (error.response && error.response.status === 0) {
                console.error("Network error - possible CORS issue");
            }
            return Promise.reject(error);
        }
    );
}

if (window.Echo) {
    window.Echo.connector.pusher.connection.bind("connected", function () {
        console.log("WebSocket connected successfully");
    });

    window.Echo.connector.pusher.connection.bind("disconnected", function () {
        console.log("WebSocket disconnected");
    });

    window.Echo.connector.pusher.connection.bind("error", function (error) {
        console.error("WebSocket error:", error);
    });

    window.Echo.connector.pusher.config.enabledTransports = ["ws", "wss"];
    window.Echo.connector.pusher.config.disabledTransports = [
        "xhr_polling",
        "xhr_streaming",
    ];
}

window.GameHelpers = {
    showError: function (message) {
        console.error(message);
        if (typeof showMessage === "function") {
            showMessage(message, "error");
        }
    },

    showSuccess: function (message) {
        console.log(message);
        if (typeof showMessage === "function") {
            showMessage(message, "success");
        }
    },

    checkConnection: async function () {
        try {
            const response = await fetch("/api/health", {
                method: "GET",
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json",
                },
            });
            return response.ok;
        } catch (error) {
            console.error("Connection check failed:", error);
            return false;
        }
    },

    retry: function (fn, retries = 3, delay = 1000) {
        return new Promise((resolve, reject) => {
            fn()
                .then(resolve)
                .catch((error) => {
                    if (retries > 0) {
                        setTimeout(() => {
                            this.retry(fn, retries - 1, delay * 2)
                                .then(resolve)
                                .catch(reject);
                        }, delay);
                    } else {
                        reject(error);
                    }
                });
        });
    },
};

if ("serviceWorker" in navigator && window.location.protocol === "https:") {
    window.addEventListener("load", function () {
        navigator.serviceWorker
            .register("/sw.js")
            .then(function (registration) {
                console.log("ServiceWorker registration successful");
            })
            .catch(function (error) {
                console.log("ServiceWorker registration failed");
            });
    });
}
