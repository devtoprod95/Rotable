export default function Placeholder({ icon, title, desc }) {
    return (
        <div className="flex min-h-full w-full flex-1 flex-col items-center justify-center rounded-2xl border border-dashed border-gray-200 bg-white px-8 py-20 text-center">
            <div className="text-6xl">{icon}</div>
            <h1 className="mt-5 text-xl font-extrabold">{title}</h1>
            <p className="mt-2 text-sm leading-relaxed text-gray-500">{desc}</p>
        </div>
    );
}
