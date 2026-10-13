export default function Placeholder({ icon, title, desc }) {
    return (
        <div className="flex w-full flex-1 flex-col items-center justify-center rounded-2xl border border-dashed border-gray-200 bg-white px-8 py-14 text-center">
            <div className="text-5xl">{icon}</div>
            <h1 className="mt-4 text-xl font-extrabold">{title}</h1>
            <p className="mt-2 text-sm leading-relaxed text-gray-500">{desc}</p>
        </div>
    );
}
