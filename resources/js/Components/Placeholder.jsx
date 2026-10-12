export default function Placeholder({ icon, title, desc }) {
    return (
        <div className="flex flex-col items-center justify-center px-8 py-24 text-center">
            <div className="text-6xl">{icon}</div>
            <h1 className="mt-5 text-xl font-extrabold">{title}</h1>
            <p className="mt-2 text-sm leading-relaxed text-gray-500">{desc}</p>
        </div>
    );
}
