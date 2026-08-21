import ParticlesBg from "../Components/ParticlesBg";

export default function GuestLayout({ children }) {
    return (
        <div className="login-page">
            <ParticlesBg />
            <div className="login-bg-shapes">
                <div className="shape shape-1"></div>
                <div className="shape shape-2"></div>
                <div className="shape shape-3"></div>
            </div>
            <div className="login-container">{children}</div>
        </div>
    );
}
