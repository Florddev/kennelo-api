"use client";

import { useEffect, useState } from "react";
import { useLocale } from "next-intl";
import { useQuery } from "@tanstack/react-query";
import { MapPin } from "lucide-react";
import { searchAddresses, type AddressSuggestionModel } from "@workspace/modules/address";
import {
    Command,
    CommandEmpty,
    CommandInput,
    CommandItem,
    CommandList,
} from "@workspace/ui/components/command";
import { Spinner } from "@workspace/ui/components/spinner";

const MIN_QUERY_LENGTH = 3;
const DEBOUNCE_MS = 300;

type AddressSearchFieldProps = {
    onSelect: (suggestion: AddressSuggestionModel) => void;
    placeholder: string;
    emptyLabel: string;
};

export function AddressSearchField({ onSelect, placeholder, emptyLabel }: AddressSearchFieldProps) {
    const locale = useLocale();
    const [query, setQuery] = useState("");
    const [debouncedQuery, setDebouncedQuery] = useState("");

    useEffect(() => {
        const timeout = setTimeout(() => setDebouncedQuery(query), DEBOUNCE_MS);
        return () => clearTimeout(timeout);
    }, [query]);

    const isSearchable = debouncedQuery.trim().length >= MIN_QUERY_LENGTH;

    const { data: suggestions = [], isFetching } = useQuery({
        queryKey: ["address-search", debouncedQuery, locale],
        queryFn: ({ signal }) => searchAddresses(debouncedQuery, { lang: locale, signal }),
        enabled: isSearchable,
        staleTime: 60_000,
    });

    const handleSelect = (suggestion: AddressSuggestionModel) => {
        onSelect(suggestion);
        setQuery("");
        setDebouncedQuery("");
    };

    return (
        <Command shouldFilter={false} className="border shadow-lg">
            <CommandInput value={query} onValueChange={setQuery} placeholder={placeholder} />
            {isSearchable && (
                <CommandList className="max-h-56">
                    {isFetching ? (
                        <div className="flex items-center justify-center gap-2 py-4 text-sm text-muted-foreground">
                            <Spinner className="size-4" />
                        </div>
                    ) : (
                        <>
                            <CommandEmpty>{emptyLabel}</CommandEmpty>
                            {suggestions.map((suggestion) => (
                                <CommandItem
                                    key={suggestion.id}
                                    value={suggestion.id}
                                    onSelect={() => handleSelect(suggestion)}
                                    className="gap-2"
                                >
                                    <MapPin className="size-4 shrink-0 text-muted-foreground" />
                                    <span className="truncate">{suggestion.label}</span>
                                </CommandItem>
                            ))}
                        </>
                    )}
                </CommandList>
            )}
        </Command>
    );
}
