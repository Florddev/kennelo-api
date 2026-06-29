export type BookingQuotePetDto = {
    id: string;
    name: string;
    animal_type_id: string;
    price_per_night: string;
    number_of_nights: number;
    subtotal: string;
};

export type BookingQuoteServiceDto = {
    id: string;
    name: string;
    quantity: number;
    unit_price: string;
    subtotal: string;
};

export type BookingQuoteDto = {
    check_in_date: string;
    check_out_date: string;
    nights: number;
    total_price: string;
    service_fee: string;
    platform_fee: string;
    activity_amount: string;
    pets: BookingQuotePetDto[];
    services: BookingQuoteServiceDto[];
};
