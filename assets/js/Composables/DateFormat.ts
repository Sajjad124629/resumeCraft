export function useHumanDate() {
    function getDaySuffix(day: number) {
        if (day > 3 && day < 21) return "th";
        switch (day % 10) {
            case 1: return "st";
            case 2: return "nd";
            case 3: return "rd";
            default: return "th";
        }
    }

    function dateFormat(dateString: string) {
        const date = new Date(dateString);

        const day = date.getDate();
        const month = date.toLocaleString("en-US", { month: "long" });
        const year = date.getFullYear();

        return `${day}${getDaySuffix(day)} ${month}, ${year}`;
    }
    return { dateFormat };
}
